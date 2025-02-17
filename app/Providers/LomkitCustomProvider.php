<?php

namespace App\Providers;

use App\Rules\CustomSearchRules;
use Illuminate\Support\ServiceProvider;
use Lomkit\Rest\Http\Requests\SearchRequest;

class LomkitCustomProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(SearchRequest::class, function ($searchRequest, $app) {
            return new class extends SearchRequest
            {
                public function rules()
                {
                    return [
                        'search' => new CustomSearchRules(
                            $this->route()->controller::newResource(),
                            $this,
                            true
                        ),
                    ];
                }
            };
        });
    }
}
