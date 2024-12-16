<?php

namespace App\Livewire;

use Livewire\Component;

class CollectionOverlap extends Component
{
    public $collections = [];

    public function mount()
    {
        $jsonPath = public_path('reports/heat_map_metadata.json');

        if (! file_exists($jsonPath)) {
            throw new \Exception('Density chart data file not found');
        }

        $jsonContent = file_get_contents($jsonPath);
        $decodedData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Error decoding JSON data: '.json_last_error_msg());
        }

        // Store in the public property
        $this->collections = $decodedData['overlap_data'];
        // Example data structure - replace with your actual data fetching logic
        // $this->collections = [
        //     'Collection A' => [
        //         'Collection A' => 100,
        //         'Collection B' => 75,
        //         'Collection C' => 45,
        //     ],
        //     'Collection B' => [
        //         'Collection A' => 75,
        //         'Collection B' => 100,
        //         'Collection C' => 60,
        //     ],
        //     'Collection C' => [
        //         'Collection A' => 45,
        //         'Collection B' => 60,
        //         'Collection C' => 100,
        //     ],
        // ];
    }

    public function render()
    {
        return view('livewire.collection-overlap', [
            'collectionsData' => json_encode($this->collections),
        ]);
    }
}
