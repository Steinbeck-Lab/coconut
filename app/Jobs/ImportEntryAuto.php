<?php

namespace App\Jobs;

use App\Models\Citation;
use App\Models\Ecosystem;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\MoleculeOrganism;
use App\Models\Organism;
use App\Models\SampleLocation;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use Str;

class ImportEntryAuto implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entry;

    public $citation_ids_array = [];

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
            $molecule = null;
            if ($this->entry->has_stereocenters) {
                $data = $this->getRepresentations('parent');
                $parent = $this->firstOrCreateMolecule($data['canonical_smiles'], $data['standard_inchi']);
                if ($parent->wasRecentlyCreated) {
                    $parent->is_parent = true;
                    $parent->is_placeholder = true;
                    $parent->variants_count += $parent->variants_count;
                    $parent = $this->assignData($parent, $data);
                    $parent->save();
                }
                $this->attachCollection($parent);

                $data = $this->getRepresentations('standardized');
                if ($data['has_stereo_defined']) {
                    $molecule = $this->firstOrCreateMolecule($data['canonical_smiles'], $data['standard_inchi']);
                    if ($molecule->wasRecentlyCreated) {
                        $molecule->status = 'DRAFT';
                        $molecule->has_stereo = true;
                        $molecule->parent_id = $parent->id;
                        $parent->has_variants = true;
                        $parent->save();
                        $molecule = $this->assignData($molecule, $data);
                        $molecule->save();
                    }
                    $this->entry->molecule_id = $molecule->id;
                    $this->entry->save();

                    $this->attachCollection($molecule);
                } else {
                    $this->entry->molecule_id = $parent->id;
                    $this->entry->save();

                    $this->attachCollection($parent);
                }
            } else {
                $data = $this->getRepresentations('standardized');
                $molecule = $this->firstOrCreateMolecule($data['canonical_smiles'], $data['standard_inchi']);
                if ($molecule->wasRecentlyCreated) {
                    $molecule = $this->assignData($molecule, $data);
                    $molecule->save();
                }
                $molecule->is_placeholder = false;
                $molecule->save();
                $this->entry->molecule_id = $molecule->id;
                $this->entry->save();
                $this->attachCollection($molecule);
            }
            // $this->entry->status = 'IMPORTED';
            $this->entry->save();

            if (! $data['has_stereo_defined'] && ! $molecule) {
                $molecule = $parent;
            }

            if ($this->entry->meta_data['new_molecule_data']['references']) {

                // Save organism details - sample location and geo location
                foreach ($this->entry->meta_data['new_molecule_data']['references'] as $reference) {

                    $doi = $reference['doi'];

                    $doiRegex = '/\b(10[.][0-9]{4,}(?:[.][0-9]+)*)\b/';
                    if ($doi && $doi != '') {
                        if (preg_match($doiRegex, $doi)) {
                            $this->fetchDOICitation($doi, $molecule);
                        } else {
                            $this->fetchCitation($doi, $molecule);
                        }
                    }

                    if ($reference['organisms']) {
                        $this->saveOrganismDetails($reference['organisms'], $molecule);
                    }
                }
            }
        }
    }

    public function firstOrCreateMolecule($canonical_smiles, $standard_inchi)
    {
        $mol = Molecule::firstOrCreate(['standard_inchi' => $standard_inchi]);
        if (! $mol->wasRecentlyCreated) {
            if ($mol->canonical_smiles != $canonical_smiles) {
                $mol->is_tautomer = true;
                $mol->save();

                $_mol = Molecule::firstOrCreate(['standard_inchi' => $standard_inchi, 'canonical_smiles' => $canonical_smiles]);
                $_mol->is_tautomer = true;
                $_mol->save();

                $relatedMols = Molecule::where('standard_inchi', $standard_inchi)->get();
                foreach ($relatedMols as $molecule) {
                    // Get all molecules except the current one
                    $relatedIds = $relatedMols->where('id', '!=', $molecule->id)
                        ->pluck('id')
                        ->toArray();

                    // Establish tautomer relationships
                    $molecule->related()->syncWithPivotValues($relatedIds, ['type' => 'tautomers'], false);
                }

                return $_mol;
            }
        }

        return $mol;
    }

    public function getRepresentations($type)
    {
        $data = json_decode($this->entry->cm_data, true);
        $mol_data = $data[$type]['representations'];
        if ($type != 'parent') {
            $mol_data['has_stereo_defined'] = $data[$type]['has_stereo_defined'];
        }

        return $mol_data;
    }

    /**
     * Attach collection to molecule.
     *
     * @param  mixed  $molecule
     * @return void
     */
    public function attachCollection($molecule)
    {
        try {
            $collection_exists = $molecule->collections()->where('collections.id', $this->entry->collection_id)->exists();
            if ($collection_exists) {
                $collection = $molecule->collections()->where('collections.id', $this->entry->collection_id)->first();
                $molecule->collections()->syncWithoutDetaching([
                    $this->entry->collection_id => [
                        'url' => $collection->pivot->url.'|'.$this->entry->link,
                        'reference' => $collection->pivot->reference.'|'.$this->entry->reference_id,
                        'mol_filename' => $collection->pivot->mol_filename.'|'.$this->entry->mol_filename,
                        'structural_comments' => $collection->pivot->structural_comments.'|'.$this->entry->structural_comments,
                    ],
                ]);
            } else {
                $molecule->collections()->attach([
                    $this->entry->collection_id => [
                        'url' => $this->entry->link,
                        'reference' => $this->entry->reference_id,
                        'mol_filename' => $this->entry->mol_filename,
                        'structural_comments' => $this->entry->structural_comments,
                    ],
                ]);
            }
        } catch (QueryException $e) {
            if ($this->isUniqueViolationException($e)) {
                // $this->attachCollection($molecule);
            }
        }
    }

    /**
     * Save organism details.
     *
     * @param  array  $organism
     * @param  mixed  $molecule
     * @return void
     */
    public function saveOrganismDetails($organisms, $molecule)
    {
        // Define collection and citation IDs once
        $newCollectionIds = [$this->entry->collection_id];
        $newCitationIds = $this->citation_ids_array;

        foreach ($organisms as $organism) {
            $parts_ids = [];
            $ecosystem_ids = [];

            $organismModel = Organism::firstOrCreate([
                'name' => $organism['name'],
                'slug' => Str::slug($organism['name']),
            ]);

            // Process parts or add null if none
            if (! empty($organism['parts'])) {
                foreach ($organism['parts'] as $part) {
                    $partModel = SampleLocation::firstOrCreate([
                        'name' => Str::title($part), // Convert to title case $part,
                        'slug' => Str::slug($part),
                    ]);
                    $parts_ids[] = [$organismModel->id, $partModel->id];
                }
            } else {
                $parts_ids[] = [$organismModel->id, null];
            }

            // Process locations or add null if none
            if (! empty($organism['locations'])) {
                foreach ($organism['locations'] as $location) {
                    $geo_location_ids = []; // Reset for each location

                    $geoLocationModel = GeoLocation::firstOrCreate([
                        'name' => $location['name'],
                    ]);

                    // Connect organism to geo location directly
                    $organismModel->geoLocations()->syncWithoutDetaching([$geoLocationModel->id]);

                    foreach ($parts_ids as $part) {
                        $geo_location_ids[] = [$part[0], $part[1], $geoLocationModel->id];
                    }

                    // Process ecosystems or add null if none
                    if (! empty($location['ecosystems'])) {
                        foreach ($location['ecosystems'] as $ecosystemName) {
                            $ecosystem = Ecosystem::firstOrCreate([
                                'name' => $ecosystemName,
                                'description' => 'Ecosystem imported from entry data',
                            ]);
                            foreach ($geo_location_ids as $geo_location) {
                                $ecosystem_ids[] = [$geo_location[0], $geo_location[1], $geo_location[2], $ecosystem->id];
                            }
                        }
                    } else {
                        foreach ($geo_location_ids as $geo_location) {
                            $ecosystem_ids[] = [$geo_location[0], $geo_location[1], $geo_location[2], null];
                        }
                    }
                }
            } else {
                $geo_location_ids = []; // Create empty array for consistency
                foreach ($parts_ids as $part) {
                    $geo_location_ids[] = [$part[0], $part[1], null];
                }
                foreach ($geo_location_ids as $geo_location) {
                    $ecosystem_ids[] = [$geo_location[0], $geo_location[1], $geo_location[2], null];
                }
            }

            Log::info('Ecosystem IDs: '.json_encode($ecosystem_ids));

            // Insert or update each relationship with collection and citation IDs using the model
            foreach ($ecosystem_ids as $pivot) {
                // Find existing pivot or create a new one
                $moleculeOrganism = MoleculeOrganism::firstOrNew([
                    'molecule_id' => $molecule->id,
                    'organism_id' => $pivot[0],
                    'sample_location_id' => $pivot[1],
                    'geo_location_id' => $pivot[2],
                    'ecosystem_id' => $pivot[3],
                ]);

                // Save first to ensure we have an ID and can check wasRecentlyCreated
                $moleculeOrganism->save();

                // If it exists, get the current IDs and merge them
                if (! $moleculeOrganism->wasRecentlyCreated) {
                    $existingCollectionIds = json_decode($moleculeOrganism->collection_ids ?? '[]', true);
                    $existingCitationIds = json_decode($moleculeOrganism->citation_ids ?? '[]', true);

                    $mergedCollectionIds = array_unique(array_merge($existingCollectionIds, $newCollectionIds));
                    $mergedCitationIds = array_unique(array_merge($existingCitationIds, $newCitationIds));

                    $moleculeOrganism->collection_ids = json_encode($mergedCollectionIds);
                    $moleculeOrganism->citation_ids = json_encode($mergedCitationIds);
                } else {
                    // It's new, so just set the initial values
                    $moleculeOrganism->collection_ids = json_encode($newCollectionIds);
                    $moleculeOrganism->citation_ids = json_encode($newCitationIds);
                }

                // Save again with the updated collection and citation IDs
                $moleculeOrganism->save();
            }
        }
    }

    /**
     * Fetch citation by citation text.
     *
     * @param  string  $citation_text
     * @param  mixed  $molecule
     * @return void
     */
    public function fetchCitation($citation_text, $molecule)
    {
        try {
            $citation = Citation::firstOrCreate(['citation_text' => $citation_text]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolationException($e)) {
                $this->fetchDOICitation($citation_text, $molecule);
            }
        }

        $molecule->citations()->syncWithoutDetaching($citation);
        $this->citation_ids_array[] = $citation->id;
    }

    /**
     * Fetch DOI citation.
     *
     * @param  string  $doi
     * @param  mixed  $molecule
     * @return void
     */
    public function fetchDOICitation($doi, $molecule)
    {
        $citation = null;

        $dois = $this->extract_dois($doi);

        foreach ($dois as $doi) {
            if ($doi) {
                // check if citation already exists
                $citation = Citation::firstOrCreate(['doi' => $doi]);

                $citationResponse = null;
                if ($citation->wasRecentlyCreated || $citation->title == '') {
                    // fetch citation from EuropePMC
                    $europemcUrl = env('EUROPEPMC_WS_API');
                    $europemcParams = [
                        'query' => 'DOI:'.$doi,
                        'format' => 'json',
                        'pageSize' => '1',
                        'resulttype' => 'core',
                        'synonym' => 'true',
                    ];
                    $europemcResponse = $this->makeRequest($europemcUrl, $europemcParams)->json();

                    if ($europemcResponse && isset($europemcResponse['resultList']['result']) && count($europemcResponse['resultList']['result']) > 0) {
                        $citationResponse = $this->formatCitationResponse($europemcResponse['resultList']['result'][0], 'europemc');
                    } else {
                        // fetch citation from CrossRef
                        $crossrefUrl = env('CROSSREF_WS_API').$doi;
                        $response = $this->makeRequest($crossrefUrl);
                        $crossrefResponse = $response ? $response->json() : null;
                        if ($crossrefResponse && isset($crossrefResponse['message'])) {
                            $citationResponse = $this->formatCitationResponse($crossrefResponse['message'], 'crossref');
                        } else {
                            // fetch citation from DataCite
                            $dataciteUrl = env('DATACITE_WS_API').$doi;
                            $response = $this->makeRequest($dataciteUrl);
                            $dataciteResponse = $response ? $response->json() : null;
                            if ($dataciteResponse && isset($dataciteResponse['data'])) {
                                $citationResponse = $this->formatCitationResponse($dataciteResponse['data'], 'datacite');
                            }
                        }
                    }
                    if ($citationResponse) {
                        if ($citationResponse['doi'] == $doi) {
                            $citation = Citation::where('doi', $citationResponse['doi'])->first();
                            if ($citation === null) {
                                $citation = Citation::create($citationResponse);
                                $citation->save();
                            } else {
                                unset($citationResponse['doi']);
                                $citation->update($citationResponse);
                                $citation->save();
                            }
                        }
                    }
                }
                $molecule->citations()->syncWithoutDetaching($citation);
                $this->citation_ids_array[] = $citation->id;
            }
        }
    }

    /**
     * Extract DOIs from a given input string.
     *
     * @param  string  $input_string
     * @return array
     */
    public function extract_dois($input_string)
    {
        $dois = [];
        $matches = [];
        // Regex pattern to match DOIs
        $pattern = '/(10\.\d{4,}(?:\.\d+)*\/\S+(?:(?!["&\'<>])\S))/i';
        // Extract DOIs using preg_match_all
        preg_match_all($pattern, $input_string, $matches);
        // Add matched DOIs to the dois array
        foreach ($matches[0] as $doi) {
            $dois[] = $doi;
        }

        return $dois;
    }

    /**
     * Format citation response based on API type.
     *
     * @param  mixed  $obj
     * @param  string  $apiType
     * @return array
     */
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

    /**
     * Check if the exception is a unique violation.
     *
     * @return bool
     */
    private function isUniqueViolationException(QueryException $e)
    {
        // Check if the SQLSTATE is 23505, which corresponds to a unique violation error
        return $e->getCode() == '23505';
    }

    /**
     * Assign data to the model.
     *
     * @param  mixed  $model
     * @param  array  $data
     * @return mixed
     */
    public function assignData($model, $data)
    {
        $model['standard_inchi'] = $data['standard_inchi'];
        $model['standard_inchi_key'] = $data['standard_inchikey'];
        $model['canonical_smiles'] = $data['canonical_smiles'];

        return $model;
    }

    /**
     * Make an HTTP request.
     *
     * @param  string  $url
     * @param  array  $params
     * @return mixed
     */
    public function makeRequest($url, $params = [])
    {
        try {
            $response = Http::timeout(600)->get($url, $params);
            if ($response->successful()) {
                return $response;
            } else {
                return null; // Handle error here
            }
        } catch (Exception $e) {
            return null; // Handle exception here
        }
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->entry->molecule_id ?? '9999999'))->expireAfter(180),
        ];
    }
}
