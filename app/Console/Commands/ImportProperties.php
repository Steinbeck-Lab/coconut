<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\Properties;
use DB;

class ImportProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-properties {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = storage_path($this->argument('file'));

        if (! file_exists($file) || ! is_readable($file)) {
            $this->error('File not found or not readable.');

            return 1;
        }

        Log::info("Reading file: " . $file);

        $batchSize = 10000;
        $header = null;
        $data = [];
        $rowCount = 0;

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, "\t")) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    try {
                        $data[] = array_combine($header, $row);
                        $rowCount++;
                        if ($rowCount % $batchSize == 0) {
                            $this->insertBatch($data);
                            $data = [];
                        }
                    } catch (\ValueError $e) {
                        Log::info('An error occurred: '.$e->getMessage());
                        Log::info($rowCount++);
                    }
                    $this->info("Inserted: $rowCount");
                }
            }
            fclose($handle);

            if (! empty($data)) {
                $this->insertBatch($data);
            }
        }

        $this->info('Properties data imported successfully!');

        return 0;
    }

    /**
     * Insert a batch of data into the database.
     *
     * @return void
     */
    private function insertBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                Properties::updateorCreate(
                    [
                        'molecule_id' => $row['id']
                    ],
                    [
                        'total_atom_count' => str_replace('"', '', $row['atom_count']),
                        'heavy_atom_count' => str_replace('"', '', $row['heavy_atom_count']),
                        'molecular_weight' => str_replace('"', '', $row['molecular_weight']),
                        'exact_molecular_weight' => str_replace('"', '', $row['exact_molecular_weight']),
                        'alogp' => str_replace('"', '', $row['alogp']),
                        'rotatable_bond_count' => str_replace('"', '', $row['rotatable_bond_count']),
                        'topological_polar_surface_area' => str_replace('"', '', $row['topological_polar_surface_area']),
                        'hydrogen_bond_acceptors' => str_replace('"', '', $row['hydrogen_bond_acceptors']),
                        'hydrogen_bond_donors' => str_replace('"', '', $row['hydrogen_bond_donors']),
                        'hydrogen_bond_acceptors_lipinski' => str_replace('"', '', $row['hydrogen_bond_acceptors_lipinski']),
                        'hydrogen_bond_donors_lipinski' => str_replace('"', '', $row['hydrogen_bond_donors_lipinski']),
                        'lipinski_rule_of_five_violations' => str_replace('"', '', $row['lipinski_rule_of_five_violations']),
                        'aromatic_rings_count' => str_replace('"', '', $row['aromatic_rings_count']),
                        'qed_drug_likeliness' => str_replace('"', '', $row['qed_drug_likeliness']),
                        'formal_charge' => str_replace('"', '', $row['formal_charge']),
                        'fractioncsp3' => str_replace('"', '', $row['fractioncsp3']),
                        'number_of_minimal_rings' => str_replace('"', '', $row['number_of_minimal_rings']),
                        'van_der_waals_volume' => str_replace('"', '', $row['van_der_waals_volume']),
                        'contains_linear_sugars' => str_replace('"', '', $row['linear_sugars']),
                        'contains_ring_sugars' => str_replace('"', '', $row['circular_sugars']),
                        'contains_sugar' => filter_var(str_replace('"', '', $row['linear_sugars']), FILTER_VALIDATE_BOOLEAN) || filter_var(str_replace('"', '', $row['circular_sugars']), FILTER_VALIDATE_BOOLEAN),
                        'murko_framework' => str_replace('"', '', $row['murko_framework']),
                        'nplikeness' => str_replace('"', '', $row['nplikeness']),
                        'molecular_formula' => str_replace('"', '', $row['molecular_formula']),
                    ]
                );
            }
        });
    }
}
