<?php

namespace App\Console\Commands;

use App\Actions\Coconut\AssignCollectionIdentifier;
use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignCollectionsIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:collection-assign-identifiers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns identifiers to all collections without identifiers';

    /**
     * Execute the console command.
     */
    public function handle(AssignCollectionIdentifier $assigner)
    {
        $this->info('Assigning identifiers to collections');

        DB::transaction(function () use ($assigner) {
            $collections = Collection::whereNull('identifier')->get();

            foreach ($collections as $collection) {
                $assigner->assign($collection);
            }
        });

        $this->info('Assigning identifiers to collections: Done');

        return 0;
    }
}
