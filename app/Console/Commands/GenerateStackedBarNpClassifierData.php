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
    protected $signature = 'COCONUT:generate-stackedbarcart-np-classifier-data
    {--sort=global : Sorting method for classes (global: sort by counts across all collections, local: sort by counts within each collection)}';

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

        // Get sorting method from options
        $sortMethod = $this->option('sort');
        $this->info('Using sorting method: '.$sortMethod);

        if ($sortMethod === 'global') {
            // Sort classes by total count across all collections
            $this->info('Sorting classes by counts across all collections...');

            $classCounts = [];
            foreach ($allClasses as $class) {
                $total = 0;
                foreach ($processedData as $collection) {
                    $total += $collection['classes'][$class] ?? 0;
                }
                $classCounts[$class] = $total;
            }

            arsort($classCounts);
            $allClasses = array_keys($classCounts);
        } elseif ($sortMethod === 'local') {
            // Sort classes within each collection
            $this->info('Sorting classes by counts within each collection...');

            // For local sorting, we'll keep the class order but sort within each collection
            foreach ($processedData as $title => $collection) {
                // Sort classes by count within this collection
                arsort($processedData[$title]['classes']);
            }

            // We still need a global ordering for the chart, so we'll use alphabetical
            sort($allClasses);
        } else {
            // Default to alphabetical if invalid option
            $this->info('Invalid sorting method specified, defaulting to alphabetical sorting...');
            sort($allClasses);
        }

        // Format for final output
        $collections = [];
        foreach ($processedData as $collection) {
            $entry = [
                'title' => $collection['title'],
            ];

            // Add all classes (even if zero)
            foreach ($allClasses as $class) {
                $entry[$class] = $collection['classes'][$class] ?? 0;
            }

            $collections[] = $entry;
        }

        $finalData = [
            'data' => array_values($collections),
            'classes' => $allClasses,
            'metadata' => [
                'totalCollections' => count($collections),
                'totalClasses' => count($allClasses),
                'sortMethod' => $sortMethod,
                'generatedAt' => now()->toDateTimeString(),
            ],
        ];

        // Save to file
        $outputPath = public_path('reports/np_classifire-stacked-bar-data.json');
        $dummyPath = public_path('reports/dummy.json');
        File::put($outputPath, json_encode($finalData, JSON_PRETTY_PRINT));
        File::put($dummyPath, json_encode($processedData, JSON_PRETTY_PRINT));

        $this->info('Data generated successfully!');
        $this->info('Output saved to: '.$outputPath);
        $this->info('Total collections: '.count($collections));
        $this->info('Total unique classes: '.count($allClasses));

        return 0;
    }
}
