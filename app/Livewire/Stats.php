<?php

namespace App\Livewire;

use Livewire\Component;

class Stats extends Component
{
    public $properties_json_data = [];

    public $bubble_frequency_json_data = [];

    public function mount()
    {
        // update the switch cases when you add new paths to this
        $plotDataFiles = [
            'reports/density_charts.json',
            'reports/bubble_frequency_charts.json',
        ];

        foreach ($plotDataFiles as $filePath) {
            $plotJson = public_path($filePath);

            try {
                if (! file_exists($plotJson)) {
                    throw new \Exception('Density chart data file not found');
                }

                $jsonContent = file_get_contents($plotJson);
                $decodedData = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Error decoding JSON data: '.json_last_error_msg());
                }

                // Store in the corresponding public properties - updade this when adding a new path
                switch ($filePath) {
                    case 'reports/density_charts.json':
                        $this->properties_json_data = $decodedData['properties'];
                        break;
                    case 'reports/bubble_frequency_charts.json':
                        $this->bubble_frequency_json_data = $decodedData;
                        break;
                    default:
                        break;
                }
            } catch (\Exception $e) {
                \Log::error('Failed to load '.$filePath.' chart data: '.$e->getMessage());
            }
        }
    }

    public function render()
    {
        return view('livewire.stats', [
            'properties_json_data' => $this->properties_json_data,
            'bubble_frequency_json_data' => $this->bubble_frequency_json_data,
        ]);
    }
}
