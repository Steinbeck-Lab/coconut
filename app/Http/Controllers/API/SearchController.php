<?php

namespace App\Http\Controllers\API;

use App\Actions\Coconut\SearchMolecule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function search(Request $request, SearchMolecule $search)
    {
        $query = $request->get('query');

        $sort = $request->get('sort');
        $type = $request->get('type') ? $request->get('type') : null;
        $tagType = $request->get('tagType') ? $request->get('tagType') : null;
        $page = $request->get('page');

        $limit = $request->get('limit');
        $limit = $limit ? $limit : 24;

        $results = [];

        $offset = $request->query('offset');

        try {
            $cacheKey = 'search.'.md5($query.$limit.$type.$sort.$tagType.$page);

            $results = Cache::remember($cacheKey, now()->addDay(), function () use ($search, $query, $limit, $type, $sort, $tagType, $page) {
                return $search->query($query, $limit, $type, $sort, $tagType, $page);
            });

            $collection = $results[1];
            $organisms = $results[2];

            return response()->json(
                [
                    'data' => $results[0],
                ],
                200
            );
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            if (str_contains(strtolower($message), strtolower('SQLSTATE[42P01]'))) {
                return response()->json(
                    [
                        'message' => 'It appears that the molecules table is not indexed. To enable search, please index molecules table and generate corresponding fingerprints.',
                    ],
                    500
                );
            }

            return response()->json(
                [
                    'message' => $message,
                ],
                500
            );
        }
    }
}
