<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateHeatMapData extends Command
{
    protected $signature = 'coconut:generate-heat-map-data';

    protected $description = 'This generates Heat Map data for collection overlaps.';

    public function handle()
    {
        Schema::table('collection_molecule', function (Blueprint $table) {
            if (! $this->hasIndex('collection_molecule', 'idx_collection_molecule_lookup')) {
                $table->index(['collection_id', 'molecule_id'], 'idx_collection_molecule_lookup');
            }
        });

        Schema::table('molecules', function (Blueprint $table) {
            if (! $this->hasIndex('molecules', 'idx_molecules_filters')) {
                $table->index(['is_parent', 'has_stereo'], 'idx_molecules_filters');
            }
        });

        $heat_map_data = [];
        $collections = Collection::all();

        // Store molecule identifiers
        foreach ($collections as $collection) {
            $this->info($collection->title);
            $molecule_identifiers = collect(DB::select("SELECT molecules.identifier
                                                        FROM molecules
                                                        INNER JOIN collection_molecule ON molecules.id = collection_molecule.molecule_id
                                                        WHERE collection_molecule.collection_id = $collection->id
                                                        AND (
                                                            molecules.is_parent = true
                                                            OR molecules.has_stereo = false
                                                        );"))->pluck('identifier')->toArray();
            // $molecule_identifiers = $collection->molecules()->where('is_parent', true)->orWhere('has_stereo', false)->pluck('identifier')->toArray();
            $molecule_identifiers = array_map(function ($item) {
                return preg_replace('/^CNP/i', '', $item);
            }, $molecule_identifiers);
            $heat_map_data['ids'][$collection->id.'|'.$collection->title] = $molecule_identifiers;
            $heat_map_data['c_counts'][$collection->id.'|'.$collection->title] = count($molecule_identifiers);
        }

        // Calculate percentage overlaps -> ol_d = overlap data
        $heat_map_data['ol_d'] = [];
        $collection_keys = array_keys($heat_map_data['ids']);

        foreach ($collection_keys as $collection1_key) {
            $this->info($collection1_key);
            $heat_map_data['ol_d'][$collection1_key] = [];
            $set1 = array_unique($heat_map_data['ids'][$collection1_key]);
            $set1_count = count($set1);

            foreach ($collection_keys as $collection2_key) {
                $set2 = array_unique($heat_map_data['ids'][$collection2_key]);
                $set2_count = count($set2);

                // Calculate intersection
                $intersection = array_intersect($set1, $set2);
                $intersection_count = count($intersection);

                // Calculate percentage overlap
                if ($set1_count > 0 && $set2_count > 0) {
                    // Using Jaccard similarity: intersection size / union size
                    $union_count = $set1_count + $set2_count - $intersection_count;
                    $overlap_percentage = ($intersection_count / $union_count) * 100;
                } else {
                    $overlap_percentage = 0;
                }

                $heat_map_data['ol_d'][$collection1_key][$collection2_key] = round($overlap_percentage, 2);

                // Add additional overlap statistics -> ol_s = overlap_stats
                $heat_map_data['ol_s'][$collection1_key][$collection2_key] = [
                    //  ol = overlap count
                    'ol' => $intersection_count,
                    'c1_count' => $set1_count,
                    'c2_count' => $set2_count,
                    'p' => round($overlap_percentage, 2),
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

        Schema::table('collection_molecule', function (Blueprint $table) {
            $table->dropIndex('idx_collection_molecule_lookup');
        });
        Schema::table('molecules', function (Blueprint $table) {
            $table->dropIndex('idx_molecules_filters');
        });
    }

    private function hasIndex($table, $indexName)
    {
        return collect(DB::select("
        SELECT indexname 
        FROM pg_indexes 
        WHERE tablename = '{$table}' 
        AND indexname = '{$indexName}'
    "))->isNotEmpty();
    }
}
