<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MoleculeResource;
use App\Models\Molecule;
use Illuminate\Http\Request;

class CompoundController extends Controller
{
    /**
     * fetch compound details
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     * path="/api/v1/compounds/{id}",
     * summary="Get compound details by COCONUT id.",
     * description="Get compound details by COCONUT id.",
     * operationId="getCompoundById",
     * tags={"search"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="COCONUT id",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     * @OA\Response(
     *    response=200,
     *    description="Successful Operation"
     *    ),
     * @OA\Response(
     *    response=404,
     *    description="Not Found"
     * )
     * )
     */
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

    /**
     * fetch compound list
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     * path="/api/v1/compounds",
     * summary="Get the list of all compounds",
     * description="Get compounds list from COCONUT",
     * operationId="getCompounds",
     * tags={"search"},
     *
     * @OA\Response(
     *    response=200,
     *    description="Successful Operation"
     *    ),
     * @OA\Response(
     *    response=404,
     *    description="Not Found"
     * )
     * )
     */
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
