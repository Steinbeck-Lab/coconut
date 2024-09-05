<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CopyNameToSynonym extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:copy-name-to-synonym';

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
        $this->info('Starting the update process...');

        DB::table('entries')
            ->select('id', 'name', 'molecule_id')
            ->whereNotNull('name')
            ->chunkById(1000, function ($rows) {
                $updates = [];

                foreach ($rows as $row) {
                    // Fetch the current synonyms for the molecule
                    $molecule = DB::table('molecules')->select('synonyms')->where('id', $row->molecule_id)->first();

                    if ($molecule) {
                        // Decode the current synonyms JSON array
                        $synonyms = json_decode($molecule->synonyms, true) ?: [];

                        // Add the new name to the synonyms array if it doesn't already exist
                        if (! in_array($row->name, $synonyms)) {
                            $synonyms[] = $row->name;
                        }

                        // Prepare the update data
                        $updates[] = [
                            'id' => $row->molecule_id,
                            'synonyms' => json_encode($synonyms),
                        ];
                    }
                }

                // Bulk update the synonyms column in the molecules table
                if (! empty($updates)) {
                    $cases = [];
                    $ids = [];
                    $bindings = [];

                    foreach ($updates as $update) {
                        $cases[] = 'WHEN ? THEN ?::json';
                        $bindings[] = $update['id'];
                        $bindings[] = $update['synonyms'];
                        $ids[] = $update['id'];
                    }

                    $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));

                    $sql = 'UPDATE molecules SET synonyms = CASE id '.implode(' ', $cases)." END WHERE id IN ($ids_placeholder)";
                    $bindings = array_merge($bindings, $ids);

                    DB::update($sql, $bindings);
                }
            });

        $this->info('Update process completed.');
    }
}
