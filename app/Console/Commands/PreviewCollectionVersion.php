<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Services\CollectionVersioning\CollectionVersionImporter;
use Illuminate\Console\Command;

class PreviewCollectionVersion extends Command
{
    protected $signature = 'coconut:preview-collection-version {new_collection_id : The new version collection ID}';

    protected $description = 'Preview SMILES diff counts for a collection version migration';

    public function handle(CollectionVersionImporter $importer): int
    {
        $collection = Collection::query()->find($this->argument('new_collection_id'));
        if (! $collection) {
            $this->error('Collection not found.');

            return self::FAILURE;
        }

        try {
            $preview = $importer->preview($collection);
            $this->table(array_keys($preview), [array_values($preview)]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
