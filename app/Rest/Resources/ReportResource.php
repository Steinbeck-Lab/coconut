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
            'comment',
            'suggested_changes',
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

    /**
     * Add create-specific validation rules
     */
    public function createRules(\Lomkit\Rest\Http\Requests\RestRequest $request)
    {
        return [
            'suggested_changes.new_molecule_data.canonical_smiles' => 'required',
            'suggested_changes.new_molecule_data.references' => 'required|array',
            'suggested_changes.new_molecule_data.references.*.doi' => 'required',
        ];
    }

    /**
     * Set default values before mutating the model
     */
    public function mutating(\Lomkit\Rest\Http\Requests\MutateRequest $request, array $requestBody, \Illuminate\Database\Eloquent\Model $model): void
    {
        // Set default values for new records
        if ($requestBody['operation'] === 'create') {
            $model->user_id = auth()->user()->id;
            $model->report_type = 'molecule';
            $model->report_category = 'new_molecule';
            $model->status = 'submitted';
        }
    }
}
