<?php

namespace App\Rules;

use Illuminate\Validation\Rule;
use Lomkit\Rest\Rules\IsNestedField;
use Lomkit\Rest\Rules\SearchRules;

class CustomSearchRules extends SearchRules
{
    public function filtersRules(\Lomkit\Rest\Http\Resource $resource, string $prefix, bool $isMaxDepth = false)
    {
        $isScoutMode = $this->request->isScoutMode();

        $operatorRules = $isScoutMode ?
            ['=', 'in', 'not in'] :
            ['=', '!=', '>', '>=', '<', '<=', 'like', 'ilike', 'not like', 'in', 'not in'];

        $fieldValidation = $isScoutMode ?
            Rule::in($resource->getScoutFields($this->request)) :
            new IsNestedField($resource, $this->request);

        $rules = array_merge(
            [
                $prefix.'.*.field' => [
                    $fieldValidation,
                    "required_without:$prefix.*.nested",
                    'string',
                ],
                $prefix.'.*.operator' => [
                    Rule::in($operatorRules),
                    'string',
                ],
                $prefix.'.*.value' => [
                    "exclude_if:$prefix.*.value,null",
                    "required_without:$prefix.*.nested",
                ],
                $prefix.'.*.type' => ! $isScoutMode ? [
                    'sometimes',
                    Rule::in('or', 'and'),
                ] : [
                    'prohibited',
                ],
                $prefix.'.*.nested' => ! $isMaxDepth && ! $isScoutMode ? [
                    'sometimes',
                    "prohibits:$prefix.*.field,$prefix.*.operator,$prefix.*.value",
                    'prohibits:value',
                    'array',
                ] : [
                    'prohibited',
                ],
            ],
            ! $isMaxDepth && ! $isScoutMode ? $this->filtersRules($resource, $prefix.'.*.nested', true) : []
        );

        return $rules;
    }
}
