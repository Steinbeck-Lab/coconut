<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Ticker;
use DB;
use Illuminate\Console\Command;

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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = 1000;
        $currentIndex = $this->fetchLastIndex() + 1;
        $data = [];
        $this->info('Assigning identifiers to collections');
        Collection::select('identifier', 'id')->where([
            ['identifier', '=', null],
        ])->chunk($batchSize, function ($collections) use (&$currentIndex) {
            $data = [];
            $header = ['id', 'identifier'];
            foreach ($collections as $collection) {
                if (! $collection->identifier) {
                    $data[] = array_combine($header, [$collection->id, $this->generateIdentifier($currentIndex)]);
                    $currentIndex++;
                }
            }
            $this->insertBatch($data);
        });
        $this->info('Assigning identifiers to collections: Done');
    }

    public function generateIdentifier($index)
    {
        $prefix = (config('app.env') === 'production') ? 'CNPC' : 'CNPC_DEV';

        return $prefix.str_pad($index, 4, '0', STR_PAD_LEFT);
    }

    public function fetchLastIndex()
    {
        $ticker = Ticker::where('type', 'collection')->first();

        return (int) $ticker->index;
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
                Collection::updateorCreate(
                    [
                        'id' => $row['id'],
                    ],
                    [
                        'identifier' => $row['identifier'],
                    ]
                );
            }
        });
    }
}
