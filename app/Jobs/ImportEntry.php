<?php

namespace App\Jobs;

use App\Models\Citation;
use App\Models\Molecule;
use App\Models\Properties;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportEntry implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entry;

    /**
     * Create a new job instance.
     */
    public function __construct($entry)
    {
        $this->entry = $entry;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->entry->status == 'PASSED') {
            if ($this->entry->has_stereocenters) {
                $data = $this->getRepresentations('parent');
                $parent = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi'], 'standard_inchi_key' => $data['standard_inchikey']]);
                $parent->is_parent = true;
                $parent->has_variants = true;
                $parent->identifier = $this->entry->coconut_id;
                $parent->variants_count += $parent->variants_count;
                $parent = $this->assignData($parent, $data);
                $parent->save();
                $this->fetchIUPACNameFromPubChem($parent);
                $this->attachProperties('parent', $parent);
                $this->attachCollection($parent);
                $this->classify($parent);

                $data = $this->getRepresentations('standardized');
                $molecule = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi'], 'standard_inchi_key' => $data['standard_inchikey']]);
                $molecule->has_stereo = true;
                $molecule->parent_id = $parent->id;
                $parent->ticker = $parent->ticker + 1;
                $molecule->identifier = $this->entry->coconut_id.'.'.$parent->ticker;
                $parent = $this->assignData($molecule, $data);
                $this->entry->molecule_id = $molecule->id;
                $this->entry->save();
                $parent->save();
                $molecule->save();
                $this->fetchIUPACNameFromPubChem($molecule);
                $this->attachProperties('standardized', $molecule);
                $this->attachCollection($molecule);
                $this->classify($molecule);
            } else {
                $data = $this->getRepresentations('standardized');
                $molecule = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi'], 'standard_inchi_key' => $data['standard_inchikey']]);
                $parent = $this->assignData($molecule, $data);
                $molecule->identifier = $this->entry->coconut_id;
                $molecule->save();
                $this->entry->molecule_id = $molecule->id;
                $this->entry->save();
                $this->fetchIUPACNameFromPubChem($molecule);
                $this->attachProperties('standardized', $molecule);
                $this->attachCollection($molecule);
                $this->classify($molecule);
            }

            if ($this->entry->doi && $this->entry->doi != '') {
                $this->fetchCitation($this->entry->doi, $molecule);
            }
        }
    }

    public function classify($molecule)
    {

        $properties = $molecule->properties;

        if ($properties->chemical_class == null || $properties->chemical_sub_class == null || $properties->chemical_super_class == null || $properties->direct_parent_classification == null) {
            $API_URL = env('API_URL', 'https://dev.api.naturalproducts.net/latest/');
            $ENDPOINT = $API_URL.'chem/classyfire/classify?smiles='.urlencode($molecule->canonical_smiles);

            try {
                $response = Http::timeout(600)->get($ENDPOINT);
                if ($response->successful()) {
                    $data = $response->json();
                    if (array_key_exists('id', $data)) {
                        $id = $data['id'];
                        sleep(5);
                        // fetch results
                        $RESULT_ENDPOINT = $API_URL.'chem/classyfire/'.$id.'/result';
                        $status = null;
                        while ($status == null) {
                            $response = Http::timeout(600)->get($RESULT_ENDPOINT);
                            if ($response->successful()) {
                                $data = $response->json();
                                if (array_key_exists('classification_status', $data)) {
                                    $status = $data['classification_status'];
                                    if ($status == 'Done') {
                                        $elements = $data['number_of_elements'];
                                        if ($elements > 0) {
                                            $entities = $data['entities'][0];
                                            $properties->direct_parent_classification = $entities['direct_parent'];
                                            $properties->chemical_sub_class = $entities['subclass'];
                                            $properties->chemical_class = $entities['class'];
                                            $properties->chemical_super_class = $entities['superclass'];
                                            $properties->save();
                                        }

                                    }
                                }

                            }
                            sleep(5);
                        }
                    }
                }
            } catch (RequestException $e) {
                Log::error('Classifyre: Request Exception occurred: '.$e->getMessage().' - '.$molecule->id, ['code' => $e->getCode()]);
            } catch (\Exception $e) {
                Log::error('Classifyre: An unexpected exception occurred: '.$e->getMessage().' - '.$molecule->id);
            }
        }

    }

    public function fetchCitation($doi, $molecule)
    {
        $citation = null;

        // check if the doi is valid
        $isDOI = preg_match('/\b(10[.][0-9]{4,}(?:[.][0-9]+)*)\b/', $doi);

        if ($isDOI) {

            //check if citation already exists
            $citation = Citation::where('doi', $doi)->first();

            if (! $citation) {
                // fetch citation from EuropePMC
                $europemcUrl = env('EUROPEPMC_WS_API');
                $europemcParams = [
                    'query' => 'DOI:'.$doi,
                    'format' => 'json',
                    'pageSize' => '1',
                    'resulttype' => 'core',
                    'synonym' => 'true',
                ];
                $europemcResponse = $this->makeRequest($europemcUrl, $europemcParams);

                if ($europemcResponse && isset($europemcResponse['resultList']['result']) && count($europemcResponse['resultList']['result']) > 0) {
                    $citationResponse = $this->formatCitationResponse($europemcResponse['resultList']['result'][0], 'europemc');
                } else {

                    // fetch citation from CrossRef
                    $crossrefUrl = env('CROSSREF_WS_API').$doi;
                    $crossrefResponse = $this->makeRequest($crossrefUrl);

                    if ($crossrefResponse && isset($crossrefResponse['message'])) {
                        $citationResponse = $this->formatCitationResponse($crossrefResponse['message'], 'crossref');
                    } else {

                        // fetch citation from DataCite
                        $dataciteUrl = env('DATACITE_WS_API').$doi;
                        $dataciteResponse = $this->makeRequest($dataciteUrl);

                        if ($dataciteResponse && isset($dataciteResponse['data'])) {
                            $citationResponse = $this->formatCitationResponse($dataciteResponse['data'], 'datacite');
                        }
                    }
                }

                if ($citationResponse) {
                    if (! Citation::where('doi', $citationResponse['doi'])->exists()) {
                        $citation = Citation::create($citationResponse);
                        $citation->save();
                    }
                }
            }

            // attach citation
            $molecule->citations()->syncWithoutDetaching($citation);
        }
    }

    public function makeRequest($url, $params = [])
    {
        try {
            $response = Http::get($url, $params);
            if ($response->successful()) {
                return $response->json();
            } else {
                return null; // Handle error here
            }
        } catch (Exception $e) {
            return null; // Handle exception here
        }
    }

    public function formatCitationResponse($obj, $apiType)
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
                    $journalTitle = $obj['title'][0];
                    $yearofPublication = isset($obj['published-online']['date-parts'][0][0]) ? $obj['published-online']['date-parts'][0][0] : '';
                    $volume = isset($obj['volume']) ? $obj['volume'] : '';
                    $issue = isset($obj['issue']) ? $obj['issue'] : '';
                    $pageInfo = isset($obj['page']) ? $obj['page'] : '';
                    $formattedCitationRes['title'] = $journalTitle;
                    if (isset($obj['author'])) {
                        $formattedCitationRes['authors'] = implode(', ', array_map(function ($author) {
                            return $author['given'].' '.$author['family'];
                        }, $obj['author']));
                    }
                    $formattedCitationRes['citation_text'] = $journalTitle.' '.$yearofPublication.' '.$volume.' ( '.$issue.' ) '.$pageInfo;
                    $formattedCitationRes['doi'] = isset($obj['DOI']) ? $obj['DOI'] : '';
                    break;
            }
        }

        return $formattedCitationRes;
    }

    public function attachCollection($molecule)
    {
        $molecule->collections()->syncWithoutDetaching($this->entry->collection);
    }

    public function fetchSynonymsCASFromPubChem($cid, $molecule)
    {
        if ($cid && $cid != 0) {
            $synonymsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.trim(preg_replace('/\s+/', ' ', $cid)).'/synonyms/txt';
            $data = Http::get($synonymsURL)->body();
            $synonyms = preg_split("/\r\n|\n|\r/", $data);
            if ($synonyms && count($synonyms) > 0) {
                if ($synonyms[0] != 'Status: 404') {
                    $pattern = "/\b[1-9][0-9]{1,5}-\d{2}-\d\b/";
                    $casIds = preg_grep($pattern, $synonyms);
                    $molecule->synonyms = $synonyms;
                    $molecule->cas = array_values($casIds);
                    $molecule->name = $synonyms[0];
                    $molecule->save();
                }
            }
        }
    }

    public function fetchIUPACNameFromPubChem($molecule)
    {
        $inchiUrl = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/inchi/cids/TXT?inchi='.urlencode($molecule->standard_inchi);
        $cid = Http::get($inchiUrl)->body();

        if ($cid && $cid != 0) {
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.trim(preg_replace('/\s+/', ' ', $cid)).'/json';
            $data = Http::get($cidPropsURL)->json();
            $props = $data['PC_Compounds'][0]['props'];
            $IUPACName = null;
            foreach ($props as $prop) {
                if ($prop['urn']['label'] == 'IUPAC Name' && $prop['urn']['name'] == 'Preferred') {
                    $IUPACName = $prop['value']['sval'];
                }
            }
            $this->fetchSynonymsCASFromPubChem($cid, $molecule);
            if ($IUPACName) {
                $molecule->iupac_name = $IUPACName;
                $molecule->save();
            }
        }
    }

    public function getRepresentations($type)
    {
        $data = json_decode($this->entry->cm_data, true);
        $data = $data[$type]['representations'];

        return $data;
    }

    public function attachProperties($type, $model)
    {
        $data = json_decode($this->entry->cm_data, true);
        $descriptors = $data[$type]['descriptors'];
        $properties = Properties::firstOrCreate(['molecule_id' => $model->id]);
        $properties->total_atom_count = $descriptors['atom_count'];
        $properties->heavy_atom_count = $descriptors['heavy_atom_count'];
        $properties->molecular_weight = $descriptors['molecular_weight'];
        $properties->molecular_formula = $this->entry->molecular_formula;
        $properties->exact_molecular_weight = $descriptors['exactmolecular_weight'];
        $properties->alogp = $descriptors['alogp'];
        $properties->rotatable_bond_count = $descriptors['rotatable_bond_count'];
        $properties->topological_polar_surface_area = $descriptors['topological_polar_surface_area'];
        $properties->hydrogen_bond_acceptors = $descriptors['hydrogen_bond_acceptors'];
        $properties->hydrogen_bond_donors = $descriptors['hydrogen_bond_donors'];
        $properties->hydrogen_bond_acceptors_lipinski = $descriptors['hydrogen_bond_acceptors_lipinski'];
        $properties->hydrogen_bond_donors_lipinski = $descriptors['hydrogen_bond_donors_lipinski'];
        $properties->lipinski_rule_of_five_violations = $descriptors['lipinski_rule_of_five_violations'];
        $properties->aromatic_rings_count = $descriptors['aromatic_rings_count'];
        $properties->qed_drug_likeliness = $descriptors['qed_drug_likeliness'];
        $properties->formal_charge = $descriptors['formal_charge'];
        $properties->fractioncsp3 = $descriptors['fractioncsp3'];
        $properties->number_of_minimal_rings = $descriptors['number_of_minimal_rings'];
        $properties->van_der_walls_volume = $descriptors['van_der_walls_volume'] == 'None' ? 0 : $descriptors['van_der_walls_volume'];
        $properties->contains_ring_sugars = $descriptors['circular_sugars'];
        $properties->contains_linear_sugars = $descriptors['linear_sugars'];
        $properties->murko_framework = $descriptors['murko_framework'];
        $properties->np_likeness = $descriptors['nplikeness'];
        $properties->molecule_id = $model->id;
        $properties->save();
    }

    public function assignData($model, $data)
    {
        $model['standard_inchi'] = $data['standard_inchi'];
        $model['standard_inchi_key'] = $data['standard_inchikey'];
        $model['canonical_smiles'] = $data['canonical_smiles'];

        return $model;
    }
}
