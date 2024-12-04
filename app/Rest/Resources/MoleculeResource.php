<?php

namespace App\Rest\Resources;

use App\Rest\Resource as RestResource;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Relations\HasOne;

class MoleculeResource extends RestResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static $model = \App\Models\Molecule::class;

    /**
     * The exposed fields that could be provided
     *
     * @param  RestRequest  $request
     */
    public function fields(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [
            'standard_inchi',
            'standard_inchi_key',
            'canonical_smiles',
            'sugar_free_smiles',
            'identifier',
            'name',
            'cas',
            'iupac_name',
            'murko_framework',
            'structural_comments',

            'name_trust_level',
            'annotation_level',
            'variants_count',

            'status',
            'active',
            'has_variants',
            'has_stereo',
            'is_tautomer',
            'is_parent',
            'is_placeholder',
        ];
    }

    /**
     * The exposed relations that could be provided
     *
     * @param  RestRequest  $request
     */
    public function relations(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [
            HasOne::make('properties', PropertiesResource::class),
        ];
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
