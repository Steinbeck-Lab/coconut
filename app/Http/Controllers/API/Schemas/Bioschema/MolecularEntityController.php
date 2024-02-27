<?php

namespace App\Http\Controllers\API\Schemas\Bioschema;

use App\Http\Controllers\Controller;
use App\Models\Molecule;
use Illuminate\Http\Request;
use Spatie\SchemaOrg\Schema;

/**
 * Implement Bioschemas MolecularEntity on COCONUT molecules to enable exporting
 * their metadata with a json endpoint and increase their findability.
 */
class MolecularEntityController extends Controller
{
    /**
     * fetch compound bioschema
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     * path="/api/v1/schemas/bioschema/{id}",
     * summary="Get bioschema schema details by COCONUT id.",
     * description="Get bioschema schema details by COCONUT id.",
     * operationId="moleculeSchema",
     * tags={"schemas"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="COCONUT id - bioschema",
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
    public function moleculeSchema(Request $request, $identifier)
    {
        $molecule = Molecule::where('identifier', $identifier)->firstOrFail();
        $moleculeSchema = Schema::MolecularEntity();
        $moleculeSchema['@id'] = $molecule->inchi_key;
        $moleculeSchema['dct:conformsTo'] = $this->prepareConformsTo();
        $moleculeSchema->identifier($molecule->identifier);
        $moleculeSchema->name($molecule->name);
        $moleculeSchema->url(env('APP_URL').'/'.'compound/'.$molecule->identifier);
        $moleculeSchema->inChI($molecule->inchi);
        $moleculeSchema->inChIKey($molecule->inchi_key);
        $moleculeSchema->iupacName($molecule->iupac_name);
        $moleculeSchema->molecularFormula($molecule->molecular_formula);
        $moleculeSchema->molecularWeight($molecule->molecular_weight);
        $moleculeSchema->smiles($molecule->cannonical_smiles);
        if ($molecule->synonyms) {
            $moleculeSchema->alternateName(array_merge($molecule->synonyms, [$molecule->cas]));
        }

        return $moleculeSchema;
    }

    public function prepareConformsTo()
    {
        $creativeWork = Schema::creativeWork();
        $creativeWork['@id'] = 'https://bioschemas.org/profiles/MolecularEntity/0.5-RELEASE';
        $confromsTo = $creativeWork;

        return $confromsTo;
    }
}
