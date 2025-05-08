<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Molecule;
use Illuminate\Console\Command;

class PublishMoleculesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:publish-molecules-auto';

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
        // Get the count of molecules to be processed
        $count = Molecule::where('status', 'DRAFT')->whereNotNull('identifier')->count();

        if ($count > 0) {
            $this->info("Total molecules to be published: {$count}");
            // Process molecules in batches of 30,000
            Molecule::where('status', 'DRAFT')->whereNotNull('identifier')
                ->lazyById(30000)
                ->each(function ($molecule) {
                    $molecule->status = 'APPROVED';
                    $molecule->active = true;
                    $molecule->save();
                });
            $this->info("Processed {$count} molecules.");
        } else {
            $this->info('No molecules to process.');
        }
    }
}
