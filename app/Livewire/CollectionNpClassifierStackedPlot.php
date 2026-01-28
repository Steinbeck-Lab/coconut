<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CollectionNpClassifierStackedPlot extends Component
{
    public $collections = [];

    public $classifierData = [];

    public $originalData = [];

    public $selectedCollections = [];

    public $searchTerm = '';

    public $limitClasses = 10; // Default limit for number of classes to show

    public $sortBy = 'count'; // Default sort - options: 'count', 'alphabetical'

    public $sortScope = 'global'; // Default sort scope - options: 'global', 'local'

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

            // Store original data for reference
            $this->originalData = $data;

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

            // Convert to indexed array for easier access
            $filteredData = array_values($filteredData);

            // Bail out if no data
            if (empty($filteredData)) {
                $this->classifierData = [
                    'data' => [],
                    'classes' => [],
                    'sortScope' => $this->sortScope,
                    'sortBy' => $this->sortBy,
                    'collectionSortedClasses' => [],
                ];

                return;
            }

            // Get all classes from data
            $allClasses = [];
            foreach ($data['classes'] as $class) {
                $allClasses[$class] = 0;
            }

            // Calculate total count for each class across all filtered collections
            foreach ($filteredData as $item) {
                foreach ($data['classes'] as $class) {
                    if (isset($item[$class])) {
                        $allClasses[$class] += (int) $item[$class];
                    }
                }
            }

            // Only include classes that have non-zero counts globally
            $nonZeroClasses = array_filter($allClasses, function ($count) {
                return $count > 0;
            });

            // Bail out if no classes with data
            if (empty($nonZeroClasses)) {
                $this->classifierData = [
                    'data' => $filteredData,
                    'classes' => [],
                    'sortScope' => $this->sortScope,
                    'sortBy' => $this->sortBy,
                    'collectionSortedClasses' => [],
                ];

                return;
            }

            // Limit classes to avoid performance issues
            $actualLimit = min((int) $this->limitClasses, 100); // Cap at 100 classes for performance
            if ((int) $this->limitClasses > 100) {
                Log::info('Limiting classes to 100 for performance (requested: '.$this->limitClasses.')');
            }

            // Sort according to global preference
            if ($this->sortBy === 'count') {
                arsort($nonZeroClasses);
            } else {
                ksort($nonZeroClasses);
            }

            // Take only the top N classes
            $topClasses = array_slice($nonZeroClasses, 0, $actualLimit, true);
            $limitedClassKeys = array_keys($topClasses);

            // Store collection-specific class orders
            $collectionSortedClasses = [];

            // Pre-process each collection
            foreach ($filteredData as $index => $collection) {
                $title = $collection['title'];

                // Make sure we have the collection structure consistent
                $processedCollection = [
                    'title' => $title,
                ];

                // Add all class values (even zero)
                foreach ($limitedClassKeys as $class) {
                    $processedCollection[$class] = isset($collection[$class]) ? (int) $collection[$class] : 0;
                }

                // Replace the original collection data with processed version
                $filteredData[$index] = $processedCollection;

                // Now process the collection-specific ordering
                if ($this->sortScope === 'local') {
                    $classValues = [];

                    // Get values for all limited classes in this collection
                    foreach ($limitedClassKeys as $class) {
                        $classValues[$class] = isset($collection[$class]) ? (int) $collection[$class] : 0;
                    }

                    // Sort classes for this collection based on sortBy preference
                    if ($this->sortBy === 'count') {
                        arsort($classValues); // Sort by count (descending)
                    } else {
                        ksort($classValues); // Sort alphabetically
                    }

                    // Store the ordered classes for this collection
                    $collectionSortedClasses[$title] = array_keys($classValues);
                } else {
                    // For global sorting, use the same order for all collections
                    $collectionSortedClasses[$title] = $limitedClassKeys;
                }
            }

            // Prepare final data structure
            $this->classifierData = [
                'data' => $filteredData,
                'classes' => $limitedClassKeys,
                'sortScope' => $this->sortScope,
                'sortBy' => $this->sortBy,
                'collectionSortedClasses' => $collectionSortedClasses,
            ];

            Log::debug('Prepared chart data', [
                'collections' => count($filteredData),
                'classes' => count($limitedClassKeys),
                'sortScope' => $this->sortScope,
                'sortBy' => $this->sortBy,
                'sample_collection_keys' => array_keys($filteredData[0]),
            ]);

            // Get all collections for the filter dropdown
            $this->collections = collect($data['data'])->map(function ($item) {
                return (object) [
                    'id' => $item['title'], // Using title as ID
                    'title' => $item['title'],
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error loading NP Classifier data: '.$e->getMessage());
            $this->classifierData = [
                'data' => [],
                'classes' => [],
                'sortScope' => $this->sortScope,
                'sortBy' => $this->sortBy,
                'collectionSortedClasses' => [],
            ];
        }
    }

    public function updateFilters()
    {
        $this->loadData();
        Log::debug('Updated classifier data', [
            'classes_count' => count($this->classifierData['classes']),
            'data_count' => count($this->classifierData['data']),
            'sort_scope' => $this->sortScope,
        ]);
    }

    public function render()
    {
        return view('livewire.collection-np-classifier-stacked-plot');
    }
}
