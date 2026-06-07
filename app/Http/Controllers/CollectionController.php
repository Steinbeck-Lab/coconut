<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Cache;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $id)
    {
        if (! str_starts_with($id, 'CNPC')) {
            abort(404);
        }

        $collection = Cache::flexible('collections.'.$id, [172800, 259200], function () use ($id) {
            return Collection::query()
                ->where('identifier', $id)
                ->orderByDesc('is_latest')
                ->orderByDesc('version')
                ->first();
        });

        if (! $collection) {
            abort(404);
        }

        $latest = $collection->is_latest
            ? $collection
            : ($collection->lineageVersionsQuery()->where('is_latest', true)->first() ?? $collection);

        $query = [
            'type' => 'tags',
            'q' => str_replace(' ', '+', $latest->title),
            'tagType' => 'dataSource',
        ];

        if ($request->has('version')) {
            $query['version'] = (int) $request->query('version');
        } elseif (! $collection->is_latest) {
            $query['version'] = $collection->version;
        }

        $baseUrl = config('app.url');

        return redirect()->to($baseUrl.'/search?'.http_build_query($query));
    }
}
