<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateNamesFromSynonyms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:update-names-from-synonyms';

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

        DB::table('molecules')
            ->whereNull('name')
            ->whereNotNull('synonyms')
            ->chunkById(1000, function ($rows) {
                $updates = [];

                foreach ($rows as $row) {
                    $synonyms = json_decode($row->synonyms, true);
                    if (! empty($synonyms) && is_array($synonyms)) {
                        $updates[] = [
                            'id' => $row->id,
                            'name' => $synonyms[0],
                        ];
                    }
                }

                // Bulk update the name column
                if (! empty($updates)) {
                    $cases = [];
                    $ids = [];
                    $bindings = [];

                    foreach ($updates as $update) {
                        $cases[] = 'WHEN ? THEN ?';
                        $bindings[] = $update['id'];
                        $bindings[] = $update['name'];
                        $ids[] = $update['id'];
                    }

                    $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));

                    $sql = 'UPDATE molecules SET name = CASE id '.implode(' ', $cases)." END WHERE id IN ($ids_placeholder)";
                    $bindings = array_merge($bindings, $ids);

                    DB::update($sql, $bindings);
                }
            });

        $this->info('Update process completed.');
    }
}
