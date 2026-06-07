<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Services\CollectionVersioning\CollectionVersionImporter;
use Illuminate\Console\Command;

class ImportCollectionVersion extends Command
{
    protected $signature = 'coconut:import-collection-version {new_collection_id : The new version collection ID}';

    protected $description = 'Run the full collection version migration pipeline';

    public function handle(CollectionVersionImporter $importer): int
    {
        $collection = Collection::query()->find($this->argument('new_collection_id'));
        if (! $collection) {
            $this->error('Collection not found.');

            return self::FAILURE;
        }

        try {
            $result = $importer->import($collection);
            $this->info('Collection version migration completed.');
            $this->table(array_keys($result), [array_values($result)]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
