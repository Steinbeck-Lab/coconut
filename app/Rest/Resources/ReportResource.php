<?php

namespace App\Rest\Resources;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Rest\Resource as RestResource;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Rest\Http\Requests\MutateRequest;
use Lomkit\Rest\Http\Requests\RestRequest;

class ReportResource extends RestResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<Model>
     */
    public static $model = Report::class;

    /**
     * The exposed fields that could be provided
     */
    public function fields(RestRequest $request): array
    {
        return [
            'title',
            'evidence',
            'comment',
            'suggested_changes',
            'mol_ids', // Added this field since we're now using it instead of mol_id_csv
        ];
    }

    /**
     * The exposed relations that could be provided
     */
    public function relations(RestRequest $request): array
    {
        return [];
    }

    /**
     * The exposed scopes that could be provided
     */
    public function scopes(RestRequest $request): array
    {
        return [];
    }

    /**
     * The exposed limits that could be provided
     */
    public function limits(RestRequest $request): array
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
    public function actions(RestRequest $request): array
    {
        return [];
    }

    /**
     * The instructions that should be linked
     */
    public function instructions(RestRequest $request): array
    {
        return [];
    }

    public function rules(RestRequest $request)
    {
        return [
            'title' => 'required',
        ];
    }

    /**
     * Add create-specific validation rules
     */
    public function createRules(RestRequest $request)
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
    public function mutating(MutateRequest $request, array $requestBody, Model $model): void
    {
        // Set default values for new records
        if ($requestBody['operation'] === 'create') {
            $model->user_id = auth()->user()->id;
            $model->report_type = 'molecule';
            $model->report_category = ReportCategory::SUBMISSION->value;
            $model->status = ReportStatus::SUBMITTED->value;
        }
    }
}
