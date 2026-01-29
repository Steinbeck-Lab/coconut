<?php

namespace App\Actions\Coconut;

use App\Models\Collection;
use App\Services\DOI\DOIService;

class AssignDOI
{
    private $doiService;

    private $identifierAssigner;

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(DOIService $doiService, AssignCollectionIdentifier $identifierAssigner)
    {
        $this->doiService = $doiService;
        $this->identifierAssigner = $identifierAssigner;
    }

    /**
     * Assign DOI to the given collection.
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
            $this->identifierAssigner->assign($collection);
            $collection->fresh()->generateDOI($this->doiService);
            echo $collection->identifier."\r\n";
        }
    }
}
