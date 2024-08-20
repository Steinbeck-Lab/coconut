<?php

namespace App\Livewire;

use Livewire\Component;
use GuzzleHttp\Client;
use App\Models\Organism;
use Log;

class ShowSourceOrganisms extends Component
{
    public $organism = null;
    public $data = null;

    public $failMessage = null;

    public function mount($record = null)
    {
        $this->organism = $record;

        $name = ucfirst(trim($record->name));

        if ($name && $name != '') {
            $this->data = $this->getOLSIRI($name, 'species');
        }
    }

    public function render()
    {
        return view('livewire.show-source-organisms', [
            'data' => $this->data ?? null,
            // 'info' => $this->organism ? $this->organism->job_info : null,
        ]);
    }

    public function selectRow($row_index)
    {
        $row = $this->data['response']['docs'][$row_index];
        $this->updateOrganismModel($row['label'], $row['iri'], $this->organism, $row['type']);
    }

    protected function updateOrganismModel($name, $iri, $organism = null, $rank = null)
    {
        $existing_organism = Organism::where('name', $name)->first();

        if ($existing_organism) {
            $current_molecules = $organism->molecules();
            $current_sample_locations = $organism->sampleLocations();

            foreach($current_sample_locations as $sample_location) {
                $sample_location->organisms()->associate($existing_organism);
                $sample_location->save();
            }

            var_dump($current_molecules);

            $existing_organism->molecules()->syncWithoutDetaching($current_molecules->pluck('id'));
            $organism->detach( $current_molecules->pluck('id') );

            redirect(env('APP_URL').'/dashboard/organisms/'.$existing_organism->id.'/edit');

        } elseif ($organism) {
            $organism->name = $name;
            $organism->iri = $iri;
            $organism->rank = $rank;
            $organism->save();
        } else {
            $this->dataerror("Organism not found in the database: $name");
        }
    }

    protected function getOLSIRI($name, $rank)
    {
        $client = new Client([
            'base_uri' => 'https://www.ebi.ac.uk/ols4/api/',
        ]);

        try {
            $response = $client->get('search', [
                'query' => [
                    'q' => $name,
                    'ontology' => ['ncbitaxon', 'efo', 'obi', 'uberon', 'taxrank'],
                    'exact' => false,
                    'obsoletes' => false,
                    'format' => 'json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return $data;
        } catch (\Exception $e) {
            // $this->dataerror("Error fetching IRI for $name: " . $e->getMessage());
            Log::error("Error fetching IRI for $name: " . $e->getMessage());
        }

        return null;
    }
}
