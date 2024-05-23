<?php

namespace App\Console\Commands;

use App\Models\Organism;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Log;

class MapOrganismNamesToOGG extends Command
{
    protected $signature = 'organisms:map-ogg';

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
        $chunkSize = 100;

        Organism::whereNull('iri')->chunk($chunkSize, function ($organisms) {
            foreach ($organisms as $organism) {
                $name = ucfirst(trim($organism->name));
                $data = null;
                if ($name && $name != '') {
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
                                $this->updateOrganismModel($name, $data, $organism, 'genus');
                                $this->info("Mapped and updated: $name");
                            } else {
                                $this->error("Could not map: $name");
                            }
                        }
                    }
                }
            }
        });
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
                if (isset($element['iri'], $element['ontologyId']) && $element['isObsolete'] === 'false') {
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
            $organism->save();
        } else {
            $this->error("Organism not found in the database: $name");
        }
    }
}
