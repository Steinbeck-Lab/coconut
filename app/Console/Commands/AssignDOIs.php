<?php

namespace App\Console\Commands;

use App\Actions\Coconut\AssignDOI;
use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignDOIs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:collection-assign-doi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns dois to all unassigned public collections';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(AssignDOI $assigner)
    {
        return DB::transaction(function () use ($assigner) {
            $collections = Collection::where([
                ['is_public', true],
                ['doi', null],
            ])->get();

            foreach ($collections as $collection) {
                $collectionDOI = $collection->doi ? $collection->doi : null;
                $assigner->assign($collection);
            }
        });
    }
}
