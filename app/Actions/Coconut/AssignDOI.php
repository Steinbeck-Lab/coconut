<?php

namespace App\Actions\Coconut;

use App\Models\Collection;
use App\Models\Ticker;
use App\Services\DOI\DOIService;

class AssignDOI
{
    private $doiService;

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(DOIService $doiService)
    {
        $this->doiService = $doiService;
    }

    /**
     * Archive the given model.
     *
     * @param  mixed  $model
     * @return void
     */
    public function assign($model)
    {
        $collection = null;
        if ($model instanceof Collection) {
            $collection = $model;
        }
        if ($collection) {
            $collectionIdentifier = $collection->identifier ? $collection->identifier : null;
            if ($collectionIdentifier == null) {
                $collectionTicker = Ticker::whereType('collection')->first();
                $collectionIdentifier = $collectionTicker->index + 1;
                $collectionTicker->index = $collectionIdentifier;
                $collectionTicker->save();

                $collection->identifier = $collectionIdentifier;
                $collection->save();
            }
            $collection->fresh()->generateDOI($this->doiService);
            echo $collection->identifier."\r\n";
        }
    }
}
