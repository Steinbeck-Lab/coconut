<?php

use App\Models\Citation;
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
                    if ($value) {
                        if (array_key_exists('identifier', $value)) {
                            $value['name'] = $value['name'].' (ID: '.$value['id'].')'.' (COCONUT ID: '.$value['identifier'].')';
                        } else {
                            $value['name'] = $value['name'].' (ID: '.$value['id'].')';
                        }
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
