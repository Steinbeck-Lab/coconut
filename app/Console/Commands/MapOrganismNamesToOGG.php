<?php

namespace App\Console\Commands;

use App\Models\Organism;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MapOrganismNamesToOGG extends Command
{
    protected $signature = 'coconut:organisms-map-ogg';

    protected $description = 'Map organism names to OGG IRIs and update the model';

    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client([
            'base_uri' => 'https://www.ebi.ac.uk/ols4/api/v2/',
        ]);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $organismsWithoutCompounds = Organism::doesntHave('molecules')->get();

        foreach ($organismsWithoutCompounds as $organism) {
            $organism->delete();
        }

        $chunkSize = 100;

        Organism::whereNull('iri')->chunk($chunkSize, function ($organisms) {
            foreach ($organisms as $organism) {
                $name = ucfirst(trim($organism->name));
                $data = null;
                if ($name) {
                    $data = $this->getOLSIRI($name, 'species');
                    if ($data) {
                        $this->updateOrganismModel($name, $data, $organism, 'species');
                        $this->info("Mapped and updated: $name");
                    } else {
                        $data = $this->getOLSIRI(explode(' ', $name)[0], 'genus');
                        if ($data) {
                            $this->updateOrganismModel($name, $data, $organism, 'genus');
                            $this->info("Mapped and updated: $name");
                        } else {
                            $data = $this->getOLSIRI(explode(' ', $name)[0], 'family');
                            if ($data) {
                                $this->updateOrganismModel($name, $data, $organism, 'family');
                                $this->info("Mapped and updated: $name");
                            } else {
                                $this->getGNFMatches($name, $organism);
                            }
                        }
                    }
                }
            }
        });
    }

    protected function getGNFMatches($name, $organism)
    {
        // echo $name .  "\n";;
        $data = [
            'text' => $name,
            'bytesOffset' => false,
            'returnContent' => false,
            'uniqueNames' => true,
            'ambiguousNames' => false,
            'noBayes' => false,
            'oddsDetails' => false,
            'language' => 'eng',
            'wordsAround' => 0,
            'verification' => true,
            'allMatches' => true,
        ];

        $client = new Client;
        $url = 'https://finder.globalnames.org/api/v1/find';

        $response = $client->post($url, [
            'json' => $data,
        ]);

        $responseBody = json_decode($response->getBody(), true);
        $names = [];
        if (isset($responseBody['names']) && count($responseBody['names']) > 0) {
            $r_name = $responseBody['names'][0];
            $matchType = $r_name['verification']['matchType'];
            echo $matchType."\n";
            if ($matchType == 'Exact' || $matchType == 'Fuzzy') {
                $iri = $r_name['verification']['bestResult']['outlink'] ?? $r_name['verification']['bestResult']['dataSourceTitleShort'];
                $ranks = $r_name['verification']['bestResult']['classificationRanks'] ?? null;
                $ranks = rtrim($ranks, '|');
                $ranks = explode('|', $ranks);
                $rank = end($ranks);
                if ($matchType == 'Fuzzy') {
                    $rank = $rank.' (fuzzy)';
                }
                $this->updateOrganismModel($name, $iri, $organism, $rank);
                $this->info("Mapped and updated: $name");
            } elseif ($matchType == 'PartialFuzzy' || $matchType == 'PartialExact') {
                $iri = $r_name['verification']['bestResult']['dataSourceTitleShort'];
                if (isset($r_name['verification']['bestResult']['classificationRanks'])) {
                    $ranks = rtrim($r_name['verification']['bestResult']['classificationRanks'] ?? '', '|') ?: null;
                    $paths = rtrim($r_name['verification']['bestResult']['classificationPath'] ?? '', '|') ?: null;
                    $ids = rtrim($r_name['verification']['bestResult']['classificationIds'] ?? '', '|') ?: null;

                    if ($ranks) {
                        $ranks = explode('|', $ranks);
                        $ranksLength = count($ranks);
                        if ($ranksLength > 1 && $paths && $ids) {
                            $pathsArray = explode('|', $paths);
                            $idsArray = explode('|', $ids);

                            if (count($pathsArray) >= $ranksLength && count($idsArray) >= $ranksLength) {
                                $parentRank = $ranks[$ranksLength - 2];
                                $parentName = $pathsArray[$ranksLength - 2];
                                $parentId = $idsArray[$ranksLength - 2];
                                $this->updateOrganismModel($name, $iri.'['.$parentName.'|'.$parentId.']', $organism, $parentRank);
                                $this->info("Mapped and updated: $name");
                            }
                        }
                    }
                }
            }
        } else {
            $this->error("Could not map: $name");
        }

    }

    protected function getOLSIRI($name, $rank)
    {
        try {
            $response = $this->client->get('entities', [
                'query' => [
                    'search' => $name,
                    'ontologyId' => 'ncbitaxon',
                    'exactMatch' => true,
                    'type' => 'class',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['elements']) && count($data['elements']) > 0) {

                $element = $data['elements'][0];
                if (isset($element['iri'], $element['ontologyId']) && $element['isObsolete'] === false) {
                    if ($rank && $rank == 'species') {
                        if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_species') {
                            return urlencode($element['iri']);
                        }
                    } elseif ($rank && $rank == 'genus') {
                        if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_genus') {
                            return urlencode($element['iri']);
                        }
                    } elseif ($rank && $rank == 'family') {
                        if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_family') {
                            return urlencode($element['iri']);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error fetching IRI for $name: ".$e->getMessage());
            Log::error("Error fetching IRI for $name: ".$e->getMessage());
        }

        return null;
    }

    protected function updateOrganismModel($name, $iri, $organism = null, $rank = null)
    {
        if (! $organism) {
            $organism = Organism::where('name', $name)->first();
        }

        if ($organism) {
            $organism->iri = $iri;
            $organism->rank = $rank;
            // Auto-generate slug if not exists
            if (! $organism->slug) {
                $organism->slug = \Illuminate\Support\Str::slug($name);
            }
            $organism->save();
        } else {
            $this->error("Organism not found in the database: $name");
        }
    }
}
