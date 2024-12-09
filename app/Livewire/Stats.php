<?php

namespace App\Livewire;

use Livewire\Component;

class Stats extends Component
{
    public $properties_json_data = [];

    public function mount()
    {
        $jsonPath = public_path('reports/density_charts.json');

        try {
            if (! file_exists($jsonPath)) {
                throw new \Exception('Density chart data file not found');
            }

            $jsonContent = file_get_contents($jsonPath);
            $decodedData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON data: '.json_last_error_msg());
            }

            // Store in the public property
            $this->properties_json_data = $decodedData['properties'];

        } catch (\Exception $e) {
            \Log::error('Failed to load density chart data: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.stats', [
            'properties_json_data' => $this->properties_json_data,
        ]);
    }
}
