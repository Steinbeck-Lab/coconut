<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MoleculeResource;
use App\Models\Molecule;
use Illuminate\Http\Request;

class CompoundController extends Controller
{
    public function id(Request $request, $id, $property = null, $key = null)
    {
        $molecule = Molecule::with(['properties', 'citations'])->where('identifier', $id)->firstOrFail();

        if (isset($property)) {
            if (isset($key)) {
                return $molecule[$property][$key];
            }

            return $molecule[$property];
        }

        return $molecule;
    }

    public function list(Request $request)
    {
        $sort = $request->get('sort');
        $size = $request->get('size');

        if ($size == null) {
            $size = 15;
        }

        if ($sort == 'latest') {
            return MoleculeResource::collection(Molecule::where('active', true)->orderByDesc('updated_at')->paginate($size));
        } else {
            return MoleculeResource::collection(Molecule::where('active', true)->paginate($size));
        }

        return MoleculeResource::collection(Molecule::where('active', true)->paginate($size));
    }
}
