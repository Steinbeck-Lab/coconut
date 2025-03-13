<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateStackedBarNpClassifierData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'COCONUT:generate-stackedbarcart-np-classifier-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate NP Classifier data for the chart from database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Generating NP Classifier data...');

        // Execute the query from the user
        $results = DB::select('
            select c.title, np_classifier_class, count(np_classifier_class) as count
            from properties p
            join molecules m on p.molecule_id=m.id
            join collection_molecule cm on cm.molecule_id=m.id
            join collections c on cm.collection_id=c.id
            GROUP by 1,2
            order by 1,3 desc
        ');

        if (empty($results)) {
            $this->error('No data found');

            return 1;
        }

        $this->info('Processing '.count($results).' rows...');

        // Process the data for the chart
        $processedData = [];
        $classesSet = [];

        foreach ($results as $row) {
            $title = $row->title;
            $class = $row->np_classifier_class;
            $count = $row->count;

            if (! isset($processedData[$title])) {
                $processedData[$title] = [
                    'title' => $title,
                    'classes' => [],
                ];
            }

            $processedData[$title]['classes'][$class] = (int) $count;
            $classesSet[$class] = true;
        }

        // Get all unique classes
        $allClasses = array_keys($classesSet);

        // Calculate global class counts for sorting
        $globalClassCounts = [];
        foreach ($allClasses as $class) {
            $total = 0;
            foreach ($processedData as $collection) {
                $total += $collection['classes'][$class] ?? 0;
            }
            $globalClassCounts[$class] = $total;
        }

        // Store sorted classes for each collection for use in frontend
        foreach ($processedData as $title => &$collection) {
            // Create two sorted versions of classes
            $classesByCount = $collection['classes'];
            arsort($classesByCount);

            $classesByName = $collection['classes'];
            ksort($classesByName);

            // Store both sorted lists
            $collection['classesByCount'] = array_keys($classesByCount);
            $collection['classesByName'] = array_keys($classesByName);
        }

        // Format for final output
        $collections = [];
        foreach ($processedData as $collection) {
            $entry = [
                'title' => $collection['title'],
                'classesByCount' => $collection['classesByCount'],
                'classesByName' => $collection['classesByName'],
            ];

            // Add all classes (even if zero)
            foreach ($allClasses as $class) {
                $entry[$class] = $collection['classes'][$class] ?? 0;
            }

            $collections[] = $entry;
        }

        // Sort global classes by total count
        arsort($globalClassCounts);
        $globalSortedClasses = array_keys($globalClassCounts);

        $finalData = [
            'data' => array_values($collections),
            'classes' => $allClasses,
            'globalSortedClasses' => $globalSortedClasses,
            'globalClassCounts' => $globalClassCounts,
            'metadata' => [
                'totalCollections' => count($collections),
                'totalClasses' => count($allClasses),
                'generatedAt' => now()->toDateTimeString(),
            ],
        ];

        // Save to file
        $outputPath = public_path('reports/np_classifire-stacked-bar-data.json');
        File::put($outputPath, json_encode($finalData, JSON_PRETTY_PRINT));

        $this->info('Data generated successfully!');
        $this->info('Output saved to: '.$outputPath);
        $this->info('Total collections: '.count($collections));
        $this->info('Total unique classes: '.count($allClasses));

        return 0;
    }
}
