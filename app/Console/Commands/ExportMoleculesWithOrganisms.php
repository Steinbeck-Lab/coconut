<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportMoleculesWithOrganisms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-molecules-with-organisms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Fetch the data
        $data = DB::table('molecule_organism')
            ->join('molecules', 'molecule_organism.molecule_id', '=', 'molecules.id')
            ->join('organisms', 'molecule_organism.organism_id', '=', 'organisms.id')
            ->select('molecule_organism.molecule_id', 'molecules.cas', 'molecules.canonical_smiles', 'molecules.identifier',
                'organisms.name as organism_name', 'organisms.iri as organism_iri', 'organisms.rank as organism_rank',
                'molecule_organism.*')
            ->get();

        // Group by molecule_id and concatenate values
        $groupedData = $data->groupBy('molecule_id')->map(function ($rows) {
            $firstRow = $rows->first();
            $moleculeId = $firstRow->molecule_id;
            $moleculeCasArray = json_decode($firstRow->cas, true);
            $moleculeCas = is_array($moleculeCasArray) && ! empty($moleculeCasArray) ? $moleculeCasArray[0] : null;
            $canonicalSmiles = $firstRow->canonical_smiles;
            $identifier = $firstRow->identifier;

            $organisms = $rows->map(function ($row) {
                $organismDetail = $row->organism_name;
                if (! empty($row->organism_iri)) {
                    $organismDetail .= ' ('.urldecode($row->organism_iri).'|'.$row->organism_rank.')';
                }

                return $organismDetail;
            })->implode('|');

            // Get pivot columns, assuming the pivot table has additional columns like 'attribute1' and 'attribute2'
            $pivotColumns = ['attribute1', 'attribute2'];
            $pivotData = [];
            foreach ($pivotColumns as $column) {
                $pivotData[$column] = $rows->pluck($column)->unique()->implode('|');
            }

            return array_merge([
                'molecule_id' => $moleculeId,
                'identifier' => $identifier,
                'molecule_cas' => $moleculeCas,
                'canonical_smiles' => $canonicalSmiles,
                'organisms' => $organisms,
            ], $pivotData);
        });

        // Convert to CSV format
        $csvContent = $groupedData->map(function ($row) {
            return implode(',', array_map(function ($value) {
                return '"'.str_replace('"', '""', $value).'"';
            }, $row));
        })->prepend(implode(',', array_keys($groupedData->first())))->implode("\n");

        // Save to a CSV file
        // The file will be created in the storage/app directory
        Storage::put('molecules_with_organisms.csv', $csvContent);

        // Alternatively, to save in the storage/app/public directory:
        // Storage::put('public/molecules_with_organisms.csv', $csvContent);

        $this->info('Export completed successfully.');
    }
}
