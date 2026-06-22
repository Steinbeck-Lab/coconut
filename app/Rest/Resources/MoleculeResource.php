<?php

namespace App\Rest\Resources;

use App\Models\Molecule;
use App\Rest\Resource as RestResource;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Relations\BelongsToMany;
use Lomkit\Rest\Relations\HasOne;

class MoleculeResource extends RestResource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<Model>
     */
    public static $model = Molecule::class;

    /**
     * The exposed fields that could be provided
     */
    public function fields(RestRequest $request): array
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
            'organism_count',

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
     */
    public function relations(RestRequest $request): array
    {
        return [
            HasOne::make('properties', PropertiesResource::class),
            BelongsToMany::make('organisms', OrganismResource::class),
        ];
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
}
