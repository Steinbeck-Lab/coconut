<?php

namespace App\Rest\Controllers;

use App\Rest\Controller as RestController;
use App\Rest\Resources\CitationResource;
use Lomkit\Rest\Http\Resource;

class CitationsController extends RestController
{
    /**
     * The resource the controller corresponds to.
     *
     * @var class-string<\Lomkit\Rest\Http\Resource>
     */
    public static $resource = CitationResource::class;
}
