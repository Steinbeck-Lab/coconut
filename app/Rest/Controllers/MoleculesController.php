<?php

namespace App\Rest\Controllers;

use App\Rest\Controller as RestController;

class MoleculesController extends RestController
{
    /**
     * The resource the controller corresponds to.
     *
     * @var class-string<\Lomkit\Rest\Http\Resource>
     */
    public static $resource = \App\Rest\Resources\MoleculeResource::class;
}
