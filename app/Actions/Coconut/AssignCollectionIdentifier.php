<?php

namespace App\Actions\Coconut;

use App\Models\Collection;
use App\Models\Ticker;

class AssignCollectionIdentifier
{
    /**
     * Assign an identifier to the given collection.
     */
    public function assign(Collection $collection): void
    {
        if ($collection->identifier) {
            return;
        }

        $ticker = Ticker::where('type', 'collection')->lockForUpdate()->first();

        if (! $ticker) {
            throw new \Exception('Collection ticker not found');
        }

        $currentIndex = (int) $ticker->index + 1;
        $prefix = (config('app.env') === 'production') ? 'CNPC' : 'CNPC_DEV';
        $identifier = $prefix.str_pad($currentIndex, 4, '0', STR_PAD_LEFT);

        $collection->identifier = $identifier;
        $collection->save();

        $ticker->index = $currentIndex;
        $ticker->save();
    }
}
