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

        $collection = Cache::remember('collections.'.$id, 172800, function () use ($id) {
            return Collection::where('identifier', $id)->first();
        });

        if (! $collection) {
            abort(404);
        }

        $query = [
            'type' => 'tags',
            'q' => urlencode($collection->title),
            'tagType' => 'dataSource',
        ];

        $baseUrl = config('app.url');

        return redirect()->to($baseUrl.'/search?'.http_build_query($query));
    }
}
