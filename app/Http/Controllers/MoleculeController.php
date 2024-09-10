<?php

namespace App\Http\Controllers;

use App\Models\Molecule;
use Cache;
use Illuminate\Http\Request;

class MoleculeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $id)
    {
        if (strpos($id, '.') === false) {
            $id .= '.0';
        }

        $molecule = Cache::remember('molecules.'.$id, 172800, function () use ($id) {
            return Molecule::where('identifier', $id)->first();
        });

        if ($molecule) {
            return view('molecule', [
                'molecule' => $molecule,
            ]);
        }

        abort(404);
    }
}
