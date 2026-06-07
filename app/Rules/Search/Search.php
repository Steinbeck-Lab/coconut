<?php

namespace App\Rules\Search;

use Lomkit\Rest\Rules\Search\Search as BaseSearch;

class Search extends BaseSearch
{
    public function buildValidationRules(string $attribute, mixed $value): array
    {
        $rules = parent::buildValidationRules($attribute, $value);
        $rules[$attribute.'.filters.*'] = (new SearchFilter)->setResource($this->resource);

        return $rules;
    }
}
