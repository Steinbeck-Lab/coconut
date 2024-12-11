<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GeneratePropertiesJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-properties-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a JSON object with column metadata for the "properties" table, including type and other metadata';

    /**
     * Determine the appropriate type based on database column type
     */
    private function determineType($dbType)
    {
        $numericTypes = ['integer', 'float', 'decimal', 'double precision', 'numeric'];
        $stringTypes = ['text', 'jsonb', 'character varying', 'varchar'];

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

    public function handle()
    {
        $tableName = 'properties';

        // Define columns to skip
        $columnsToSkip = [
            'id',
            'molecule_id',
            'molecular_formula',
            'murcko_framework',
            'created_at',
            'updated_at',
        ];

        // Fetch all columns from the table
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

        // Filter out the columns to skip
        $columns = array_diff($columns, $columnsToSkip);

        $jsonOutput = [];

        // Invert the filter map
        $filterMap = getFilterMap();
        $invertedFilterMap = array_flip($filterMap);

        foreach ($columns as $column) {
            // Fetch column type
            $typeResult = DB::select('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$tableName, $column]);

            if (empty($typeResult)) {
                continue;
            }

            $dbType = $typeResult[0]->data_type;
            $type = $this->determineType($dbType);

            // Prepare the basic structure with type
            $columnData = [
                'type' => $type,
                'label' => ucfirst(str_replace('_', ' ', $column)),
            ];

            if ($type === 'range') {
                // For numeric types, calculate min and max
                $result = DB::table($tableName)
                    ->selectRaw("MIN($column) as min, MAX($column) as max")
                    ->first();

                $columnData['range'] = [
                    'min' => $result->min,
                    'max' => $result->max,
                ];
            } elseif ($type === 'boolean') {
                // For boolean types, set true and false as possible values
                $columnData['values'] = [true, false];
            } elseif ($type === 'select') {
                // For text or JSONB types, fetch unique values
                $uniqueValues = DB::table($tableName)
                    ->select($column)
                    ->distinct()
                    ->pluck($column)
                    ->filter()
                    ->values()
                    ->toArray();

                $columnData['unique_values'] = $uniqueValues;
            }

            // Determine the key for the JSON output
            $key = $invertedFilterMap[$column] ?? $column;

            // Add the column data using the determined key
            $jsonOutput[$key] = $columnData;
        }

        // Convert the output to JSON
        $json = json_encode($jsonOutput, JSON_PRETTY_PRINT);

        // Output the JSON to the console
        $this->info($json);

        // Optionally save the JSON to a file
        $filePath = public_path('assets/properties_metadata.json');
        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        file_put_contents($filePath, $json);

        $this->info('JSON metadata saved to public/assets/properties_metadata.json');

        // Provide some statistics
        $this->info(sprintf(
            "Generated metadata for %d columns:\n%s",
            count($jsonOutput),
            implode(', ', array_keys($jsonOutput))
        ));
    }
}
