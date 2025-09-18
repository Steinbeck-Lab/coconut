<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;

class GenerateHeatMapData extends Command
{
    protected $signature = 'coconut:generate-heat-map-data';

    protected $description = 'This generates Heat Map data for collection overlaps.';

    public function handle()
    {
        $heat_map_data = [];
        $collections = Collection::all();

        // Store molecule identifiers
        foreach ($collections as $collection) {
            $molecule_identifiers = $collection->molecules()->pluck('identifier')->toArray();
            $molecule_identifiers = array_map(function ($item) {
                return preg_replace('/^CNP/i', '', $item);
            }, $molecule_identifiers);
            $heat_map_data['ids'][$collection->id.'|'.$collection->title] = $molecule_identifiers;
        }

        // Calculate percentage overlaps -> ol_d = overlap data
        $heat_map_data['ol_d'] = [];
        $collection_keys = array_keys($heat_map_data['ids']);

        foreach ($collection_keys as $collection1_key) {
            $heat_map_data['ol_d'][$collection1_key] = [];
            $set1 = array_unique($heat_map_data['ids'][$collection1_key]);
            $set1_count = count($set1);

            foreach ($collection_keys as $collection2_key) {
                $set2 = array_unique($heat_map_data['ids'][$collection2_key]);
                $set2_count = count($set2);

                // Calculate intersection
                $intersection = array_intersect($set1, $set2);
                $intersection_count = count($intersection);

                // Calculate directional overlap percentages
                // What percentage of collection1's molecules are in collection2
                $c1_in_c2_percentage = ($set1_count > 0)
                    ? ($intersection_count / $set1_count) * 100
                    : 0;

                // What percentage of collection2's molecules are in collection1
                $c2_in_c1_percentage = ($set2_count > 0)
                    ? ($intersection_count / $set2_count) * 100
                    : 0;

                // For the main heatmap data, use the larger percentage
                // This shows the stronger relationship between the collections
                $overlap_percentage = max($c1_in_c2_percentage, $c2_in_c1_percentage);

                $heat_map_data['ol_d'][$collection1_key][$collection2_key] = round($overlap_percentage, 2);

                // Add additional overlap statistics -> ol_s = overlap_stats
                $heat_map_data['ol_s'][$collection1_key][$collection2_key] = [
                    'ol' => $intersection_count,
                    'c1_count' => $set1_count,
                    'c2_count' => $set2_count,
                    'p' => round($overlap_percentage, 2),
                    'c1_in_c2_p' => round($c1_in_c2_percentage, 2), // Percentage of collection1 contained in collection2
                    'c2_in_c1_p' => round($c2_in_c1_percentage, 2), // Percentage of collection2 contained in collection1
                ];
            }
        }
        unset($heat_map_data['ids']);

        $json = json_encode($heat_map_data, JSON_UNESCAPED_SLASHES);

        // Save the JSON to a file
        $filePath = public_path('reports/heat_map_metadata.json');
        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        file_put_contents($filePath, $json);

        $this->info('JSON metadata saved to public/reports/heat_map_metadata.json');
    }
}
