<?php

namespace App\Http\Controllers\API;

use App\Actions\Coconut\SearchMolecule;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SearchController extends Controller
{
    public function search(Request $request, SearchMolecule $search)
    {

        // Validate and sanitize input parameters
        $validator = Validator::make($request->all(), [
            'query' => 'nullable|string|max:1000',
            'type' => ['nullable', 'string', Rule::in(['text', 'smiles', 'inchi', 'inchikey', 'substructure', 'exact', 'similarity', 'tags', 'filters'])],
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => ['nullable', 'string', Rule::in(['recent', 'relevance'])],
            'tagType' => 'nullable|string|max:100|regex:/^[a-zA-Z_]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        $query = $this->sanitizeQuery($request->get('query'));
        $sort = $request->get('sort');
        $type = $request->get('type') ? $request->get('type') : null;
        $tagType = $request->get('tagType') ? $request->get('tagType') : null;
        $page = (int) ($request->get('page') ?? 1);
        $limit = (int) ($request->get('limit') ?? 24);

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

    /**
     * Sanitize query input to prevent injection attacks
     */
    private function sanitizeQuery(?string $query): ?string
    {
        if (empty($query)) {
            return null;
        }

        // Remove null bytes and control characters
        $query = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $query);

        // Trim whitespace
        $query = trim($query);

        // Limit length
        $query = substr($query, 0, 1000);

        return $query;
    }
}
