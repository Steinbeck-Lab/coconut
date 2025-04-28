<?php

namespace App\Rest\Resources;

use App\Rest\Resource as RestResource;
use Illuminate\Database\Eloquent\Model;

class ReportResource extends RestResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static $model = \App\Models\Report::class;

    /**
     * The exposed fields that could be provided
     */
    public function fields(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [
            'title',
            'evidence',
            'doi',
            'comment',
            'suggested_changes',
            'mol_id_csv',
        ];
    }

    /**
     * The exposed relations that could be provided
     */
    public function relations(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    /**
     * The exposed scopes that could be provided
     */
    public function scopes(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    /**
     * The exposed limits that could be provided
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
     */
    public function actions(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    /**
     * The instructions that should be linked
     */
    public function instructions(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [];
    }

    public function rules(\Lomkit\Rest\Http\Requests\RestRequest $request)
    {
        return [
            'title' => 'required',
        ];
    }
}
