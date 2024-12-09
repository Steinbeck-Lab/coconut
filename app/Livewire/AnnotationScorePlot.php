<?php

namespace App\Livewire;

use Livewire\Component;

class AnnotationScorePlot extends Component
{
    // Making our data properties public so they're accessible in the view
    public $chartData_overall = [];

    public $chartData_collections = [];

    public $selectedCollections = [];

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
            $this->chartData_overall = $decodedData;

        } catch (\Exception $e) {
            \Log::error('Failed to load density chart data: '.$e->getMessage());

            $this->chartData = [
                'properties' => [
                    'alogp' => [
                        'overall' => [
                            'density_data' => [],
                            'statistics' => [],
                        ],
                        'collections' => [],
                    ],
                ],
            ];
        }
    }

    public function render()
    {
        // Pass the data to the view explicitly
        return view('livewire.annotation-score-plot', [
            'data' => $this->chartData_overall,
        ]);
    }
}
