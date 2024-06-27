<?php

namespace App\Rest;

use Lomkit\Rest\Http\Resource as RestResource;

abstract class Resource extends RestResource
{
    /**
     * Build a "search" query for fetching resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function searchQuery(\Lomkit\Rest\Http\Requests\RestRequest $request, \Illuminate\Contracts\Database\Eloquent\Builder $query)
    {
        return $query;
    }

    /**
     * Build a query for mutating resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function mutateQuery(\Lomkit\Rest\Http\Requests\RestRequest $request, \Illuminate\Contracts\Database\Eloquent\Builder $query)
    {
        return $query;
    }

    /**
     * Build a "destroy" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function destroyQuery(\Lomkit\Rest\Http\Requests\RestRequest $request, \Illuminate\Contracts\Database\Eloquent\Builder $query)
    {
        return $query;
    }

    /**
     * Build a "restore" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function restoreQuery(\Lomkit\Rest\Http\Requests\RestRequest $request, \Illuminate\Contracts\Database\Eloquent\Builder $query)
    {
        return $query;
    }

    /**
     * Build a "forceDelete" query for the given resource.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function forceDeleteQuery(\Lomkit\Rest\Http\Requests\RestRequest $request, \Illuminate\Contracts\Database\Eloquent\Builder $query)
    {
        return $query;
    }
}
