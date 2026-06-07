<?php

namespace App\Http\Requests\Rest;

use App\Rules\Search\Search;
use Lomkit\Rest\Http\Requests\SearchRequest as LomkitSearchRequest;

class SearchRequest extends LomkitSearchRequest
{
    public function rules(): array
    {
        $resource = $this->route()->controller::newResource();

        return [
            'search' => (new Search)->setResource($resource),
        ];
    }
}
