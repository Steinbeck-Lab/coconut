<?php

use App\Models\Citation;
use App\Models\Molecule;
use Filament\Forms\Components\KeyValue;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

function npScore($old_value)
{
    $old_min = -4.5;
    $old_max = 4.5;
    $new_min = 0;
    $new_max = 30;

    return ($old_value - $old_min) / ($old_max - $old_min) * ($new_max - $new_min) + $new_min;
}

function getReportTypes()
{
    return [
        'molecule' => 'Molecule',
        'citation' => 'Citation',
        'collection' => 'Collection',
        'organism' => 'Organism',
    ];
}

function doiRegxMatch($doi)
{
    $doiRegex = '/\b(10[.][0-9]{4,}(?:[.][0-9]+)*)\b/';

    return preg_match($doiRegex, $doi);
}

function fetchDOICitation($doi)
{
    $citationResponse = null;
    $europemcUrl = env('EUROPEPMC_WS_API');
    $europemcParams = [
        'query' => 'DOI:'.$doi,
        'format' => 'json',
        'pageSize' => '1',
        'resulttype' => 'core',
        'synonym' => 'true',
    ];
    $europemcResponse = makeRequest($europemcUrl, $europemcParams);

    if ($europemcResponse && isset($europemcResponse['resultList']['result']) && count($europemcResponse['resultList']['result']) > 0) {
        $citationResponse = formatCitationResponse($europemcResponse['resultList']['result'][0], 'europemc');
    } else {
        // fetch citation from CrossRef
        $crossrefUrl = env('CROSSREF_WS_API').$doi;
        $crossrefResponse = makeRequest($crossrefUrl);
        if ($crossrefResponse && isset($crossrefResponse['message'])) {
            $citationResponse = formatCitationResponse($crossrefResponse['message'], 'crossref');
        } else {
            // fetch citation from DataCite
            $dataciteUrl = env('DATACITE_WS_API').$doi;
            $dataciteResponse = makeRequest($dataciteUrl);
            if ($dataciteResponse && isset($dataciteResponse['data'])) {
                $citationResponse = formatCitationResponse($dataciteResponse['data'], 'datacite');
            } else {
                // fetch citation internally from CoconutDB
                $coconutResponse = Citation::where('doi', $doi)->first();
                if ($coconutResponse) {
                    return $coconutResponse;
                }
            }
        }
    }

    return $citationResponse;
}

function makeRequest($url, $params = [])
{
    try {
        $response = Http::timeout(600)->get($url, $params);
        if ($response->successful()) {
            return $response->json();
        } else {
            return null; // Handle error here
        }
    } catch (Exception $e) {
        return null; // Handle exception here
    }
}

function formatCitationResponse($obj, $apiType)
{
    $journalTitle = '';
    $yearofPublication = '';
    $volume = '';
    $issue = '';
    $pageInfo = '';
    $formattedCitationRes = [];

    if ($obj) {
        switch ($apiType) {
            case 'europemc':
                $journalTitle = isset($obj['journalInfo']['journal']['title']) ? $obj['journalInfo']['journal']['title'] : '';
                $yearofPublication = isset($obj['journalInfo']['yearOfPublication']) ? $obj['journalInfo']['yearOfPublication'] : '';
                $volume = isset($obj['journalInfo']['volume']) ? $obj['journalInfo']['volume'] : '';
                $issue = isset($obj['journalInfo']['issue']) ? $obj['journalInfo']['issue'] : '';
                $pageInfo = isset($obj['pageInfo']) ? $obj['pageInfo'] : '';
                $formattedCitationRes['title'] = isset($obj['title']) ? $obj['title'] : '';
                $formattedCitationRes['authors'] = isset($obj['authorString']) ? $obj['authorString'] : '';
                $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication.' '.$volume.' ( '.$issue.' ) '.$pageInfo;
                $formattedCitationRes['doi'] = isset($obj['doi']) ? $obj['doi'] : '';
                break;
            case 'datacite':
                $journalTitle = isset($obj['attributes']['titles'][0]['title']) ? $obj['attributes']['titles'][0]['title'] : '';
                $yearofPublication = isset($obj['attributes']['publicationYear']) ? $obj['attributes']['publicationYear'] : null;
                $volume = isset($obj['attributes']['volume']) ? $obj['attributes']['volume'] : '';
                $issue = isset($obj['attributes']['issue']) ? $obj['attributes']['issue'] : '';
                $pageInfo = isset($obj['attributes']['page']) ? $obj['attributes']['page'] : '';
                $formattedCitationRes['title'] = $journalTitle;
                if (isset($obj['attributes']['creators'])) {
                    $formattedCitationRes['authors'] = implode(', ', array_map(function ($author) {
                        return $author['name'];
                    }, $obj['attributes']['creators']));
                }
                $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication;
                $formattedCitationRes['doi'] = isset($obj['attributes']['doi']) ? $obj['attributes']['doi'] : '';
                break;
            case 'crossref':
                $journalTitle = isset($obj['title'][0]) ? $obj['title'][0] : '';
                $yearofPublication = isset($obj['published-online']['date-parts'][0][0]) ? $obj['published-online']['date-parts'][0][0] : '';
                $volume = isset($obj['volume']) ? $obj['volume'] : '';
                $issue = isset($obj['issue']) ? $obj['issue'] : '';
                $pageInfo = isset($obj['page']) ? $obj['page'] : '';
                $formattedCitationRes['title'] = $journalTitle;
                if (isset($obj['author'])) {
                    $formattedCitationRes['authors'] = implode(', ', array_map(function ($author) {
                        $fullName = '';
                        if (isset($author['given'])) {
                            $fullName .= $author['given'].' ';
                        }
                        if (isset($author['family'])) {
                            $fullName .= $author['family'];
                        }

                        return $fullName;
                    }, $obj['author']));
                }
                $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication.' '.$volume.' ( '.$issue.' ) '.$pageInfo;
                $formattedCitationRes['doi'] = isset($obj['DOI']) ? $obj['DOI'] : '';
                break;
        }
    }

    return $formattedCitationRes;
}

function customAuditLog($event_type, $model_objects, $column_name, $currentValue, $newValue)
{
    foreach ($model_objects as $model_object) {
        $model_object->auditEvent = $event_type;
        $model_object->isCustomEvent = true;
        $model_object->auditCustomOld = [
            $column_name => $currentValue,
        ];
        $model_object->auditCustomNew = [
            $column_name => $newValue,
        ];
        Event::dispatch(AuditCustom::class, [$model_object]);
    }
}

function changeAudit(array $data): array
{
    // set the user_id and user_type if they are null (commands)
    if (! $data['user_id']) {
        $data['user_id'] = 11;
    }
    if (! $data['user_type']) {
        $data['user_type'] = 'App\Models\User';
    }

    if (($data['event'] === 're-assign' || $data['event'] === 'detach' || $data['event'] === 'attach' || $data['event'] === 'sync') && $data['old_values'] && $data['new_values']) {
        $whitelist = [
            // 'id' => 'id',
            'name' => 'name',
            'title' => 'title',
            // 'identifier' => 'identifier',
        ];

        $changed_data = [];

        $changed_model = array_keys($data['old_values']) ? array_keys($data['old_values'])[0] : array_keys($data['new_values'])[0];
        $changed_data['old_values'] = $data['old_values'][$changed_model] instanceof \Illuminate\Database\Eloquent\Model ? [$data['old_values'][$changed_model]->toArray()] : $data['old_values'][$changed_model];
        $changed_data['new_values'] = $data['new_values'][$changed_model] instanceof \Illuminate\Database\Eloquent\Model ? [$data['new_values'][$changed_model]->toArray()] : $data['new_values'][$changed_model];

        if (! is_int($changed_data['old_values'])) {
            foreach ($changed_data as $key_type => $changed_data_values) {
                $data[$key_type][$changed_model] = [];
                foreach ($changed_data_values as $key => $value) {
                    $value = is_array($value) ? $value : $value->toArray();
                    if (array_key_exists('name', $value)) {
                        if (array_key_exists('identifier', $value)) {
                            $value['name'] = $value['name'].' (ID: '.$value['id'].')'.' (COCONUT ID: '.$value['identifier'].')';
                        } else {
                            $value['name'] = $value['name'].' (ID: '.$value['id'].')';
                        }
                    } else {
                        $value['title'] = $value['title'].' (ID: '.$value['id'].')';
                    }
                    $data[$key_type][$changed_model][$key] = array_intersect_key($value, $whitelist);
                }
            }
        }
    }

    $data['old_values'] = flattenArray($data['old_values']);
    $data['new_values'] = flattenArray($data['new_values']);

    return $data;
}

function flattenArray(array $array, $prefix = ''): array
{
    $result = [];

    foreach ($array as $key => $value) {
        // Determine the new key based on whether a prefix exists
        $newKey = $prefix === '' ? $key : $prefix.'.'.$key;

        // If the value is an array, flatten it recursively
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $newKey));
        } else {
            // Add the value to the result with the appropriate key
            $result[$newKey] = $value;
        }
    }

    return $result;
}

function getChangesToDisplayModal($data)
{
    $key_values = [];
    $overall_changes = getOverallChanges($data);

    foreach ($overall_changes as $key => $value) {
        if (count($value['changes']) == 0) {
            continue;
        }
        array_push($key_values, KeyValue::make($key)
            ->addable(false)
            ->deletable(false)
            ->keyLabel($value['key'])
            ->valueLabel($value['value'])
            ->editableKeys(false)
            ->editableValues(false)
            ->default(
                $value['changes']
            ));
    }

    return $key_values;
}

function getOverallChanges($data)
{
    $overall_changes = [];
    $geo_location_changes = [];
    $molecule = Molecule::where('identifier', $data['mol_id_csv'])->first();

    $db_geo_locations = $molecule->geo_locations->pluck('name')->toArray();
    $deletable_locations = array_key_exists('existing_geo_locations', $data) ? array_diff($db_geo_locations, $data['existing_geo_locations']) : [];
    $new_locations = array_key_exists('new_geo_locations', $data) ? (is_string($data['new_geo_locations']) ? $data['new_geo_locations'] : implode(',', $data['new_geo_locations'])) : null;
    if (count($deletable_locations) > 0 || $new_locations) {
        $key = implode(',', $deletable_locations) == '' ? ' ' : implode(',', $deletable_locations);
        $geo_location_changes[$key] = $new_locations;
        $overall_changes['geo_location_changes'] = [
            'key' => 'Delete',
            'value' => 'Add',
            'changes' => $geo_location_changes,
            'delete' => $deletable_locations,
            'add' => $new_locations,
        ];
    }

    $synonym_changes = [];
    $db_synonyms = $molecule->synonyms;
    $deletable_synonyms = array_key_exists('existing_synonyms', $data) && ! empty($db_synonyms) ? array_diff($db_synonyms, $data['existing_synonyms']) : [];
    $new_synonyms = array_key_exists('new_synonyms', $data) ? (is_string($data['new_synonyms']) ? $data['new_synonyms'] : implode(',', $data['new_synonyms'])) : null;
    if (count($deletable_synonyms) > 0 || $new_synonyms) {
        $key = implode(',', $deletable_synonyms) == '' ? ' ' : implode(',', $deletable_synonyms);
        $synonym_changes[$key] = $new_synonyms;
        $overall_changes['synonym_changes'] = [
            'key' => 'Delete',
            'value' => 'Add',
            'changes' => $synonym_changes,
            'delete' => $deletable_synonyms,
            'add' => $new_synonyms,
        ];
    }

    $name_change = [];
    if (array_key_exists('name', $data) && $data['name'] && $data['name'] != $molecule->name) {
        $name_change[$molecule->name] = $data['name'];
        $overall_changes['name_change'] = [
            'key' => 'Old',
            'value' => 'New',
            'changes' => $name_change,
            'old' => $molecule->name,
            'new' => $data['name'],
        ];
    }

    $cas_changes = [];
    $db_cas = $molecule->cas;
    $deletable_cas = array_key_exists('existing_cas', $data) && ! empty($db_cas) ? array_diff($db_cas, $data['existing_cas']) : [];
    $new_cas = array_key_exists('new_cas', $data) ? (is_string($data['new_cas']) ? $data['new_cas'] : implode(',', $data['new_cas'])) : null;
    if (count($deletable_cas) > 0 || $new_cas) {
        $key = implode(',', $deletable_cas) == '' ? ' ' : implode(',', $deletable_cas);
        $cas_changes[$key] = $new_cas;
        $overall_changes['cas_changes'] = [
            'key' => 'Delete',
            'value' => 'Add',
            'changes' => $cas_changes,
            'delete' => $deletable_cas,
            'add' => $new_cas,
        ];
    }

    $organism_changes = [];
    $db_organisms = $molecule->organisms->pluck('name')->toArray();
    $deletable_organisms = array_key_exists('existing_organisms', $data) ? array_diff($db_organisms, $data['existing_organisms']) : [];
    $new_organisms = [];
    $new_organisms_form_data = [];
    if (array_key_exists('new_organisms', $data)) {
        foreach ($data['new_organisms'] as $organism) {
            if ($organism['name']) {
                $new_organisms[] = $organism['name'];
                $new_organisms_form_data[] = $organism;
            }
        }
    }
    if (count($deletable_organisms) > 0 || count($new_organisms) > 0) {
        $key = implode(',', $deletable_organisms) == '' ? ' ' : implode(',', $deletable_organisms);
        $organism_changes[$key] = implode(',', $new_organisms);
        $overall_changes['organism_changes'] = [
            'key' => 'Delete',
            'value' => 'Add',
            'changes' => $organism_changes,
            'delete' => $deletable_organisms,
            'add' => $new_organisms_form_data,
        ];
    }

    $citation_changes = [];
    $db_citations = $molecule->citations->where('title', '<>', null)->pluck('title')->toArray();
    $deletable_citations = array_key_exists('existing_citations', $data) ? array_diff($db_citations, $data['existing_citations']) : [];
    $new_citations = [];
    $new_citations_form_data = [];
    if (array_key_exists('new_citations', $data)) {
        foreach ($data['new_citations'] as $ciation) {
            if ($ciation['title']) {
                $new_citations[] = $ciation['title'];
                $new_citations_form_data[] = $ciation;
            }
        }
    }
    if (count($deletable_citations) > 0 || count($new_citations) > 0) {
        $key = implode(',', $deletable_citations) == '' ? ' ' : implode(',', $deletable_citations);
        $citation_changes[$key] = implode(',', $new_citations);
        $overall_changes['citation_changes'] = [
            'key' => 'Delete',
            'value' => 'Add',
            'changes' => $citation_changes,
            'delete' => $deletable_citations,
            'add' => $new_citations_form_data,
        ];
    }

    return $overall_changes;
}

function copyChangesToCuratorJSON($record, $data)
{
    $temp = $data;
    $data = $record->toArray();

    $data['suggested_changes']['curator']['existing_geo_locations'] = $temp['existing_geo_locations'] ?? $record['suggested_changes']['curator']['existing_geo_locations'];
    $data['suggested_changes']['curator']['new_geo_locations'] = $temp['new_geo_locations'] ?? $record['suggested_changes']['curator']['new_geo_locations'];
    $data['suggested_changes']['curator']['approve_geo_locations'] = $temp['approve_geo_locations'] ?? false;

    $data['suggested_changes']['curator']['existing_synonyms'] = $temp['existing_synonyms'] ?? $record['suggested_changes']['curator']['existing_synonyms'];
    $data['suggested_changes']['curator']['new_synonyms'] = $temp['new_synonyms'] ?? $record['suggested_changes']['curator']['new_synonyms'];
    $data['suggested_changes']['curator']['approve_synonyms'] = $temp['approve_synonyms'] ?? false;

    $data['suggested_changes']['curator']['name'] = $temp['name'] ?? $record['suggested_changes']['curator']['name'];
    $data['suggested_changes']['curator']['approve_name'] = $temp['approve_name'] ?? false;

    $data['suggested_changes']['curator']['existing_cas'] = $temp['existing_cas'] ?? $record['suggested_changes']['curator']['existing_cas'];
    $data['suggested_changes']['curator']['new_cas'] = $temp['new_cas'] ?? $record['suggested_changes']['curator']['new_cas'];
    $data['suggested_changes']['curator']['approve_cas'] = $temp['approve_cas'] ?? false;

    $data['suggested_changes']['curator']['existing_organisms'] = $temp['existing_organisms'] ?? $record['suggested_changes']['curator']['existing_organisms'];
    $data['suggested_changes']['curator']['new_organisms'] = $temp['new_organisms'] ?? $record['suggested_changes']['curator']['new_organisms'];
    $data['suggested_changes']['curator']['approve_existing_organisms'] = $temp['approve_existing_organisms'] ?? false;

    $data['suggested_changes']['curator']['existing_citations'] = $temp['existing_citations'] ?? $record['suggested_changes']['curator']['existing_citations'];
    $data['suggested_changes']['curator']['new_citations'] = $temp['new_citations'] ?? $record['suggested_changes']['curator']['new_citations'];
    $data['suggested_changes']['curator']['approve_existing_citations'] = $temp['approve_existing_citations'] ?? false;

    return $data;
}

function prepareComment($reason)
{
    return [[
        'timestamp' => now(),
        'changed_by' => auth()->user()->id,
        'comment' => $reason,
    ]];
}

function convert_italics_notation($text)
{
    // Use preg_replace to replace ~{text} with <i>text</i>
    $converted_text = preg_replace('/~\{([^}]*)\}/', '<i>$1</i>', $text);

    return $converted_text;
}

function remove_italics_notation($text)
{
    // Use preg_replace to find all instances of ~{something} and replace with just something
    $converted_text = preg_replace('/~\{([^}]*)\}/', '$1', $text);

    return $converted_text;
}

function getFilterMap()
{
    return [
        'mf' => 'molecular_formula',
        'mw' => 'molecular_weight',
        'hac' => 'heavy_atom_count',
        'tac' => 'total_atom_count',
        'arc' => 'aromatic_ring_count',
        'rbc' => 'rotatable_bond_count',
        'mrc' => 'minimal_number_of_rings',
        'fc' => 'formal_charge',
        'cs' => 'contains_sugar',
        'crs' => 'contains_ring_sugars',
        'cls' => 'contains_linear_sugars',
        'npl' => 'np_likeness_score',
        'alogp' => 'alogp',
        'topopsa' => 'topo_psa',
        'fsp3' => 'fsp3',
        'hba' => 'h_bond_acceptor_count',
        'hbd' => 'h_bond_donor_count',
        'ro5v' => 'rule_of_5_violations',
        'lhba' => 'lipinski_h_bond_acceptor_count',
        'lhbd' => 'lipinski_h_bond_donor_count',
        'lro5v' => 'lipinski_rule_of_5_violations',
        'ds' => 'found_in_databases',
        'class' => 'chemical_class',
        'subclass' => 'chemical_sub_class',
        'superclass' => 'chemical_super_class',
        'parent' => 'direct_parent_classification',
        'np_class' => 'np_classifier_class',
        'np_superclass' => 'np_classifier_superclass',
        'np_pathway' => 'np_classifier_pathway',
        'np_glycoside' => 'np_classifier_is_glycoside',
        'org' => 'organism',
        'cite' => 'ciatation',
    ];
}
