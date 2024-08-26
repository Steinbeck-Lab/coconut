<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OrganismMoleculeCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:organism-molecule-counts';

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
        $i = 0;
        $moleculeCounts = DB::table('molecule_organism')
            ->select(DB::raw('organism_id as id, COUNT(molecule_id) as count'))
            ->groupBy('organism_id')
            ->orderBy('organism_id')
            ->get();

        foreach ($moleculeCounts as $count) {
            DB::table('organisms')
                ->where('id', $count->id)
                ->update(['molecule_count' => $count->count]);
        }

        $this->info('Updatin the residual organisms.');

        DB::table('organisms')
            ->whereNotIn('id', $moleculeCounts->pluck('id'))
            ->update(['molecule_count' => 0]);

        $this->info('Update process completed.');
    }
}
