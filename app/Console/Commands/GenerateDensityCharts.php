<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class GenerateDensityCharts extends Command
{
    protected $signature = 'coconut:generate-density-charts {tables?* : Format: table1:col1,col2 table2:col1,col3}';

    protected $description = 'Generate density charts data for molecular tables and columns';

    private $density_bins = 30;

    private $excluded_collections = '63';

    private $columnsToSkip = [
        'properties' => [
            'id',
            'molecule_id',
            'molecular_formula',
            'murcko_framework',
            'created_at',
            'updated_at',
        ],
        'molecules' => [
            'id',
            'name_trust_level',
            'parent_id',
            'variants_count',
            'ticker',
            'organism_count',
            'geo_count',
            'citation_count',
            'collection_count',
            'synonym_count',
            'created_at',
            'updated_at',
        ],
    ];

    private $defaultTables = [
        'properties',
        'molecules',
    ];

    private $includedColumnsForCollectionStats = ['annotation_level', 'np_likeness'];

    public function handle()
    {
        Schema::table('collection_molecule', function (Blueprint $table) {
            if (! Schema::hasIndex('collection_molecule', 'idx_collection_molecule_molecule')) {
                $table->index('molecule_id', 'idx_collection_molecule_molecule');
            }
            if (! Schema::hasIndex('collection_molecule', 'fk_collection_molecule_collection_id')) {
                $table->index('collection_id', 'fk_collection_molecule_collection_id');
            }
        });
        Schema::table('molecules', function (Blueprint $table) {
            // Index for active and parent/variant filtering conditions
            if (! Schema::hasIndex('molecules', 'idx_molecules_active_parent_variants')) {
                $table->index(['active', 'is_parent', 'has_variants'], 'idx_molecules_active_parent_variants');
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            // Index for molecule_id as it's used in joins
            if (! Schema::hasIndex('properties', 'idx_properties_molecule_id')) {
                $table->index('molecule_id', 'idx_properties_molecule_id');
            }
        });

        Schema::table('collections', function (Blueprint $table) {
            // Index for collections id and title as they're used in grouping
            if (! Schema::hasIndex('collections', 'idx_collections_id_title')) {
                $table->index(['id', 'title'], 'idx_collections_id_title');
            }
        });

        $tableColumnMap = $this->parseArguments();
        $densityData = $this->generateDensityData($tableColumnMap);

        $filePath = public_path('reports/density_charts.json');
        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        File::put($filePath, json_encode($densityData, JSON_UNESCAPED_SLASHES));

        $this->info('Density charts data saved to: public/reports/density_charts.json');

        Schema::table('collection_molecule', function (Blueprint $table) {
            $table->dropIndex('idx_collection_molecule_molecule');
            $table->dropIndex('fk_collection_molecule_collection_id');
        });
        Schema::table('molecules', function (Blueprint $table) {
            $table->dropIndex('idx_molecules_active_parent_variants');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('idx_properties_molecule_id');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('idx_collections_id_title');
        });
    }

    private function determineType($dbType)
    {
        $numericTypes = ['int2', 'int4', 'int8', 'float4', 'float8', 'numeric', 'decimal'];
        $stringTypes = ['text', 'jsonb', 'character varying', 'varchar', 'char', 'bpchar', 'uuid'];

        if (in_array($dbType, $numericTypes)) {
            return 'range';
        }
        if ($dbType === 'boolean') {
            return 'boolean';
        }
        if (in_array($dbType, $stringTypes)) {
            return 'select';
        }

        return 'unknown';
    }

    private function parseArguments(): array
    {
        $args = $this->argument('tables');
        $tableColumnMap = [];

        // If no arguments provided, use all numeric columns from default tables
        if (empty($args)) {
            foreach ($this->defaultTables as $table) {
                if (Schema::hasTable($table)) {
                    $tableColumnMap[$table] = $this->getValidColumns($table);
                }
            }

            return $tableColumnMap;
        }

        // Parse table:column1,column2 format
        foreach ($args as $arg) {
            [$table, $columns] = array_pad(explode(':', $arg), 2, '');

            if (! Schema::hasTable($table)) {
                $this->warn("Table '$table' does not exist, skipping...");

                continue;
            }

            // If no columns specified, use all valid numeric columns
            if (empty($columns)) {
                $tableColumnMap[$table] = $this->getValidColumns($table);

                continue;
            }

            $requestedColumns = explode(',', $columns);
            $validColumns = $this->getValidColumns($table);

            $tableColumnMap[$table] = array_filter($requestedColumns, function ($col) use ($validColumns, $table) {
                if (! in_array($col, $validColumns)) {
                    $columnType = Schema::getColumnType($table, $col);
                    $dataType = $this->determineType($columnType);
                    if ($dataType !== 'range') {
                        $this->warn("Column '$col' in table '$table' is not numeric (type: $dataType), skipping...");
                    } else {
                        $this->warn("Column '$col' in table '$table' is invalid or excluded, skipping...");
                    }

                    return false;
                }

                return true;
            });
        }

        return $tableColumnMap;
    }

    private function getValidColumns(string $table): array
    {
        $allColumns = Schema::getColumnListing($table);
        $excludedColumns = $this->columnsToSkip[$table] ?? [];

        return array_filter($allColumns, function ($column) use ($table, $excludedColumns) {
            if (in_array($column, $excludedColumns)) {
                return false;
            }

            $columnType = Schema::getColumnType($table, $column);
            $dataType = $this->determineType($columnType);

            // Only include numeric (range) columns
            return $dataType === 'range';
        });
    }

    private function generateDensityData(array $tableColumnMap): array
    {
        $result = [];

        foreach ($tableColumnMap as $table => $columns) {
            if (empty($columns)) {
                continue;
            }

            $this->info("Processing table: $table");
            $result[$table] = [];

            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($table, $column);
                $dataType = $this->determineType($columnType);

                if ($dataType !== 'range') {
                    continue;
                }

                $this->info("  Calculating density for column: $column (type: $dataType)");

                $result[$table][$column] = $this->calculateDensity($table, $column, $dataType);
            }
        }

        return $result;
    }

    private function calculateDensity(string $table, string $column, string $dataType): ?array
    {
        $statsForColumn = [];

        Schema::table($table, function (Blueprint $table1) use ($table, $column) {
            $indexName = 'idx_'.$table1->getTable().'_'.$column;
            if (! Schema::hasIndex($table, $indexName)) {
                $table1->index([$column], $indexName);
            }
        });

        // For overall stats
        if ($table != 'molecules') {
            $stats = DB::select("
                                    SELECT MIN($column) as min_val,
                                            MAX($column) as max_val,
                                            AVG($column) as mean,
                                            COUNT($column) as count,
                                            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY $column) as median
                                    FROM $table p
                                     JOIN molecules m ON p.molecule_id = m.id
                                    join collection_molecule cm on p.molecule_id = cm.molecule_id
                                    WHERE m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE) and cm.collection_id!=63;
                                ")[0];
        } else {
            $stats = DB::select("
                                    SELECT MIN($column) as min_val,
                                            MAX($column) as max_val,
                                            AVG($column) as mean,
                                            COUNT($column) as count,
                                            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY $column) as median
                                    FROM $table m
                                    join collection_molecule cm on m.id = cm.molecule_id
                                    WHERE m.active = TRUE 
                                        AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)
                                        and cm.collection_id!=63;
                                ")[0];
        }

        $statsForColumn = [
            'type' => $dataType,
            'overall' => $this->getDensityStats($stats, $table, $column),
            'collections' => [],
        ];

        // For collection-wise stats
        if (in_array($column, $this->includedColumnsForCollectionStats)) {
            if ($table !== 'molecules') {
                $collectionsStats = DB::table('collections as c')
                    ->join('collection_molecule as cm', 'c.id', '=', 'cm.collection_id')
                    ->join($table.' as p', 'cm.molecule_id', '=', 'p.molecule_id')
                    ->join('molecules as m', 'p.molecule_id', '=', 'm.id')
                    ->whereRaw('m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)')
                    ->whereNotNull("p.$column")
                    ->groupBy('c.id', 'c.title')
                    ->selectRaw("
                                            c.id,
                                            c.title,
                                            MIN(p.$column) as min_val,
                                            MAX(p.$column) as max_val,
                                            AVG(p.$column) as mean,
                                            COUNT(p.$column) as count,
                                            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY p.$column) as median
                                        ")
                    ->get();
            } else {
                $collectionsStats = DB::table('collections as c')
                    ->join('collection_molecule as cm', 'c.id', '=', 'cm.collection_id')
                    ->join($table.' as m', 'cm.molecule_id', '=', 'm.id')
                    ->whereRaw('m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)')
                    ->whereNotNull("m.$column")
                    ->groupBy('c.id', 'c.title')
                    ->selectRaw("
                                            c.id,
                                            c.title,
                                            MIN(m.$column) as min_val,
                                            MAX(m.$column) as max_val,
                                            AVG(m.$column) as mean,
                                            COUNT(m.$column) as count,
                                            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY m.$column) as median
                                        ")
                    ->get();
            }

            foreach ($collectionsStats as $collectionStats) {
                $statsForColumn['collections'][$collectionStats->title] = $this->getDensityStats($collectionStats, $table, $column);
                // $this->info("For column: $column  Calculated density for collection: {$collectionStats->title}");
            }
        }

        $this->info("Calculated density for column: $column");

        Schema::table($table, function (Blueprint $table1) use ($table, $column) {
            $indexName = 'idx_'.$table1->getTable().'_'.$column;
            if (Schema::hasIndex($table, $indexName)) {
                $table1->dropIndex($indexName);
            }
        });

        return $statsForColumn;
    }

    private function getDensityStats($stats, $table, $column)
    {
        if (! $stats || $stats->min_val === null || $stats->count === 0) {
            return null;
        }

        $id = $stats->id ?? null;
        $min = $stats->min_val;
        $max = $stats->max_val;

        // Check if column is integer type
        $columnType = Schema::getColumnType($table, $column);
        $isInteger = in_array($columnType, ['int2', 'int4', 'int8']);

        if ($min === $max) {
            return [
                'density_data' => [],
                'statistics' => [
                    'min' => $min,
                    'max' => $max,
                    'count' => $stats->count,
                    'mean' => $stats->mean,
                    'median' => $stats->median,
                    'single_value' => true,
                ],
            ];
        }

        $bins = [];
        $query = '';

        if ($isInteger) {
            // For integer columns, create a bin for each discrete value

            if ($table != 'molecules') {
                $query = "
                    SELECT distinct p.$column as value, count(*) as count 
                    FROM $table p
                    JOIN molecules m ON p.molecule_id = m.id ";

                // For collection-wise stats
                if ($id) {
                    $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id = $id";
                } else {
                    $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id not in ($this->excluded_collections) ";
                }

                $query .= " WHERE m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)
                    GROUP BY p.$column";
            } else {
                $query = "
                    SELECT distinct m.$column as value, count(*) as count 
                    FROM $table m ";

                // For collection-wise stats
                if ($id) {
                    $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id = $id";
                } else {
                    $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id not in ($this->excluded_collections) ";
                }

                $query .= " WHERE m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)
                    GROUP BY m.$column";
            }
            $result = DB::select($query);

            foreach ($result as $row) {
                $bins[] = [
                    'x' => $row->value,
                    'y' => $row->count,
                    'range' => [$row->value, $row->value],
                ];
            }

            // For integer values, normalize by total count only (no bin width division)
            $totalCount = array_sum(array_column($bins, 'y'));
            foreach ($bins as &$bin) {
                $bin['y'] = $bin['y'] / $totalCount;
            }
        } else {
            // Original continuous value logic
            $binWidth = ($max - $min) / $this->density_bins;

            for ($i = 0; $i < $this->density_bins; $i++) {
                $binStart = $min + ($i * $binWidth);
                $binEnd = $binStart + $binWidth;

                if ($table != 'molecules') {
                    $query = "
                        SELECT count(distinct m.id) as count 
                        FROM $table p 
                        JOIN molecules m ON p.molecule_id = m.id ";

                    // For collection-wise stats
                    if ($id) {
                        $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id = $id";
                    } else {
                        $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id not in ($this->excluded_collections) ";
                    }

                    $query .= " WHERE (CAST(p.$column AS DECIMAL(10,2)) BETWEEN ? AND ?)
                        AND m.active = TRUE 
                        AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)";

                    $count = DB::select($query, [$binStart, $binEnd])[0]->count;
                } else {
                    $query = "
                        SELECT count(distinct m.id) as count 
                        FROM $table m ";

                    // For collection-wise stats
                    if ($id) {
                        $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id = $id";
                    } else {
                        $query .= " JOIN collection_molecule cm ON cm.molecule_id = m.id AND cm.collection_id not in ($this->excluded_collections) ";
                    }

                    $query .= " WHERE (CAST(m.$column AS DECIMAL(10,2)) BETWEEN ? AND ?)
                        AND m.active = TRUE 
                        AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE)";

                    $count = DB::select($query, [$binStart, $binEnd])[0]->count;
                }

                $bins[] = [
                    'x' => ($binStart + $binEnd) / 2,
                    'y' => $count,
                    'range' => [$binStart, $binEnd],
                ];
            }

            // Normalize continuous values by count and bin width
            $totalCount = array_sum(array_column($bins, 'y'));
            foreach ($bins as &$bin) {
                $bin['y'] = $bin['y'] / ($totalCount * $binWidth);
            }
        }

        return [
            'density_data' => $bins,
            'statistics' => [
                'min' => $min,
                'max' => $max,
                'count' => $stats->count,
                'mean' => $stats->mean,
                'median' => $stats->median,
                'is_discrete' => $isInteger,
            ],
        ];
    }
}
