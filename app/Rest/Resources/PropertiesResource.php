<?php

namespace App\Rest\Resources;

use App\Rest\Resource as RestResource;
use Lomkit\Rest\Relations\HasOne;

class PropertiesResource extends RestResource
{
    use \Lomkit\Rest\Concerns\Resource\DisableAuthorizations;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static $model = \App\Models\Properties::class;

    /**
     * The exposed fields that could be provided
     *
     * @param  RestRequest  $request
     */
    public function fields(\Lomkit\Rest\Http\Requests\RestRequest $request): array
    {
        return [
            'total_atom_count',
            'heavy_atom_count',
            'molecular_weight',
            'exact_molecular_weight',
            'molecular_formula',
            'alogp',
            'topological_polar_surface_area',
            'rotatable_bond_count',
            'hydrogen_bond_acceptors',
            'hydrogen_bond_donors',
            'hydrogen_bond_acceptors_lipinski',
            'hydrogen_bond_donors_lipinski',
            'lipinski_rule_of_five_violations',
            'aromatic_rings_count',
            'qed_drug_likeliness',
            'formal_charge',
            'fractioncsp3',
            'number_of_minimal_rings',
            'van_der_walls_volume',
            'contains_sugar',
            'contains_ring_sugars',
            'contains_linear_sugars',
            'murcko_framework',
            'np_likeness',
            'chemical_class',
            'chemical_sub_class',
            'chemical_super_class',
            'direct_parent_classification',
            'np_classifier_pathway',
            'np_classifier_superclass',
            'np_classifier_class',
            'np_classifier_is_glycoside',
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
            HasOne::make('molecule', MoleculeResource::class),
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
