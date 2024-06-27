<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use App\Models\Ticker;
use DB;
use Illuminate\Console\Command;

class AssignIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'molecules:assign-identifiers';

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
        $this->info('Mapping parents');
        Molecule::select('identifier', 'id', 'has_variants')->where([
            ['has_stereo', '=', false],
            ['identifier', '=', null],
        ])->chunk($batchSize, function ($molecules) use (&$currentIndex) {
            $data = [];
            $header = ['id', 'identifier'];
            foreach ($molecules as $molecule) {
                if (! $molecule->identifier) {
                    $data[] = array_combine($header, [$molecule->id, $this->generateIdentifier($currentIndex)]);
                    $currentIndex++;
                }
            }
            $this->insertBatch($data);
        });
        $this->info('Mapping parents: Done');

        $this->info('Mapping variants');
        Molecule::select('identifier', 'id', 'has_variants')->where(
            [
                ['is_parent', '=', true],
                ['identifier', '!=', null],
            ])->chunk($batchSize, function ($molecules) {
                $data = [];
                $header = ['id', 'identifier'];
                foreach ($molecules as $molecule) {
                    $i = 1;
                    $variants = $molecule->variants;
                    foreach ($variants as $variant) {
                        $data[] = array_combine($header, [$variant->id, $molecule->identifier.'.'.$i]);
                        $i++;
                    }
                }
                $this->insertBatch($data);
            });
        $this->info('Mapping variants: Done');
    }

    public function generateIdentifier($index)
    {
        return 'CNP'.str_pad($index, 7, '0', STR_PAD_LEFT);
    }

    public function fetchLastIndex()
    {
        $ticker = Ticker::where('type', 'molecule')->first();

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
                Molecule::updateorCreate(
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
