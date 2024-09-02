<?php

namespace App\Rest\Resources;

use App\Rest\Resource as RestResource;

class CollectionResource extends RestResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static $model = \App\Models\Collection::class;

    /**
     * The exposed fields that could be provided
     *
     * @param  RestRequest  $request
     */
    public function fields(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [
            'title',
            'description',
            'identifier',
            'url',
        ];
    }

    /**
     * The exposed relations that could be provided
     *
     * @param  RestRequest  $request
     */
    public function relations(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    /**
     * The exposed scopes that could be provided
     *
     * @param  RestRequest  $request
     */
    public function scopes(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    /**
     * The exposed limits that could be provided
     *
     * @param  RestRequest  $request
     */
    public function limits(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [
            10,
            25,
            50,
        ];
    }

    /**
     * The actions that should be linked
     *
     * @param  RestRequest  $request
     */
    public function actions(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    /**
     * The instructions that should be linked
     *
     * @param  RestRequest  $request
     */
    public function instructions(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }
}
