<?php

namespace App\Livewire;

use Livewire\Component;
use Symfony\Component\Console\Logger\ConsoleLogger;

class DensityPlot extends Component
{
    // Making our data properties public so they're accessible in the view
    public $chartData = [];
    public $selectedCollections = [];

    public function mount()
    {
        $jsonPath = public_path('reports/density_charts.json');
        
        try {
            if (!file_exists($jsonPath)) {
                throw new \Exception('Density chart data file not found');
            }

            $jsonContent = file_get_contents($jsonPath);
            $decodedData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON data: ' . json_last_error_msg());
            }

            // Store in the public property
            $this->chartData = $decodedData['properties']['np_likeness']['overall']['density_data'];
            // $this->chartData = $decodedData['properties']['np_likeness']['overall'];

        } catch (\Exception $e) {
            \Log::error('Failed to load density chart data: ' . $e->getMessage());
            
            $this->chartData = [
                'properties' => [
                    'alogp' => [
                        'overall' => [
                            'density_data' => [],
                            'statistics' => []
                        ],
                        'collections' => []
                    ]
                ]
            ];
        }
    }

    public function toggleCollection($collection)
    {
        if (in_array($collection, $this->selectedCollections)) {
            $this->selectedCollections = array_diff($this->selectedCollections, [$collection]);
        } else {
            $this->selectedCollections[] = $collection;
        }
        
        $this->dispatch('collectionsUpdated', $this->selectedCollections);
    }

    public function render()
    {
        // Pass the data to the view explicitly
        return view('livewire.density-plot', [
            'data' => $this->chartData
        ]);
    }
}