<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CollectionNpClassifierStackedPlot extends Component
{
    public $collections = [];

    public $classifierData = [];

    public $selectedCollections = [];

    public $searchTerm = '';

    public $limitClasses = 10; // Default limit for number of classes to show

    public $sortBy = 'count'; // Default sort - options: 'count', 'alphabetical'

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        try {
            // Read from the generated JSON file
            $jsonPath = public_path('reports/np_classifire-stacked-bar-data.json');

            if (! file_exists($jsonPath)) {
                Log::warning('NP Classifier data file not found: '.$jsonPath);
                $this->classifierData = ['data' => [], 'classes' => []];

                return;
            }

            $jsonData = file_get_contents($jsonPath);
            $data = json_decode($jsonData, true);

            if (! $data || ! isset($data['data']) || ! isset($data['classes'])) {
                Log::warning('Invalid JSON structure in NP Classifier data file');
                $this->classifierData = ['data' => [], 'classes' => []];

                return;
            }

            // Apply filters to the data
            $filteredData = $data['data'];

            // Apply collection filter if any selected
            if (! empty($this->selectedCollections)) {
                $filteredData = array_filter($filteredData, function ($item) {
                    return in_array($item['title'], $this->selectedCollections);
                });
            }

            // Apply search filter
            if (! empty($this->searchTerm)) {
                $searchTerm = strtolower($this->searchTerm);
                $filteredData = array_filter($filteredData, function ($item) use ($searchTerm, $data) {
                    // Search in collection title
                    if (stripos($item['title'], $searchTerm) !== false) {
                        return true;
                    }

                    // Search in classifier classes
                    foreach ($data['classes'] as $class) {
                        if (stripos($class, $searchTerm) !== false && isset($item[$class]) && $item[$class] > 0) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            // Get all classes from filtered data
            $allClasses = [];
            foreach ($filteredData as $item) {
                foreach ($data['classes'] as $class) {
                    if (isset($item[$class]) && $item[$class] > 0) {
                        if (! isset($allClasses[$class])) {
                            $allClasses[$class] = 0;
                        }
                        $allClasses[$class] += $item[$class];
                    }
                }
            }

            // Sort classes
            if ($this->sortBy === 'count') {
                arsort($allClasses);
            } else {
                ksort($allClasses);
            }

            // Limit classes
            $topClasses = array_slice($allClasses, 0, (int) $this->limitClasses, true);

            // Prepare filtered data structure
            $this->classifierData = [
                'data' => array_values($filteredData),
                'classes' => array_keys($topClasses),
            ];

            // Get all collections for the filter
            $this->collections = collect($data['data'])->map(function ($item) {
                return (object) [
                    'id' => $item['title'], // Using title as ID
                    'title' => $item['title'],
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error loading NP Classifier data: '.$e->getMessage());
            $this->classifierData = ['data' => [], 'classes' => []];
        }
    }

    public function updateFilters()
    {
        $this->loadData();
        Log::debug('Updated classifier data', [
            'classes_count' => count($this->classifierData['classes']),
            'data_count' => count($this->classifierData['data']),
        ]);
        // $this->dispatchBrowserEvent('np-classifier-data-updated', ['data' => $this->classifierData]);
        // $this->emit('chartDataUpdated', $this->classifierData);
    }

    public function render()
    {
        return view('livewire.collection-np-classifier-stacked-plot');
    }
}
