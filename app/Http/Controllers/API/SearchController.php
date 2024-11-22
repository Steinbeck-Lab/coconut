<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Molecule;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            set_time_limit(300);

            $queryType = 'text';
            $results = [];

            $limit = $request->query('limit');
            $sort = $request->query('sort');
            $limit = $limit ? $limit : 24;
            $page = $request->query('page');
            $tagType = $request->get('tagType') ? $request->get('tagType') : null;

            $offset =
                (($page != null && $page != 'null' && $page != 0 ? $page : 1) -
                    1) *
                $limit;

            $query = $request->get('query');

            $type = $request->query('type')
                ? $request->query('type')
                : $request->get('type');

            if ($type) {
                $queryType = $type;
            } else {
                //inchi
                $re =
                    '/^((InChI=)?[^J][0-9BCOHNSOPrIFla+\-\(\)\\\\\/,pqbtmsih]{6,})$/i';
                preg_match_all($re, $query, $imatches, PREG_SET_ORDER, 0);

                if (count($imatches) > 0 && substr($query, 0, 6) == 'InChI=') {
                    $queryType = 'inchi';
                }

                //inchikey
                $re = '/^([0-9A-Z\-]+)$/i';
                preg_match_all($re, $query, $ikmatches, PREG_SET_ORDER, 0);
                if (
                    count($ikmatches) > 0 &&
                    substr($query, 14, 1) == '-' &&
                    strlen($query) == 27
                ) {
                    $queryType = 'inchikey';
                }

                // smiles
                $re = '/^([^J][0-9BCOHNSOPrIFla@+\-\[\]\(\)\\\\\/%=#$]{6,})$/i';
                preg_match_all($re, $query, $matches, PREG_SET_ORDER, 0);

                if (count($matches) > 0 && substr($query, 14, 1) != '-') {
                    $queryType = 'smiles';
                }
            }

            $filterMap = [
                'mf' => 'molecular_formula',

                'mw' => 'molecular_weight',
                'hac' => 'heavy_atom_count',
                'tac' => 'total_atom_count',

                'arc' => 'aromatic_ring_count',
                'rbc' => 'rotatable_bond_count',
                'mrc' => 'minimal_number_of_rings',
                'fc' => 'formal_charge',
                'cs' => 'contains_sugar',
                'crs' => 'contains_ring_sugars',
                'cls' => 'contains_linear_sugars',

                'npl' => 'np_likeness_score',
                'alogp' => 'alogp',
                'topopsa' => 'topo_psa',
                'fsp3' => 'fsp3',
                'hba' => 'h_bond_acceptor_count',
                'hbd' => 'h_bond_donor_count',
                'ro5v' => 'rule_of_5_violations',
                'lhba' => 'lipinski_h_bond_acceptor_count',
                'lhbd' => 'lipinski_h_bond_donor_count',
                'lro5v' => 'lipinski_rule_of_5_violations',
                'ds' => 'found_in_databases',

                'class' => 'chemical_class',
                'subclass' => 'chemical_sub_class',
                'superclass' => 'chemical_super_class',
                'parent' => 'direct_parent_classification',

            ];

            $queryType = strtolower($queryType);

            $statement = null;

            if ($queryType == 'smiles' || $queryType == 'substructure') {
                $statement =
                    "select id, COUNT(*) OVER () from mols where m@>'".
                    $query.
                    "' limit ".
                    $limit.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'inchi') {
                $statement =
                    "select id, COUNT(*) OVER () from molecules where standard_inchi LIKE '%".
                    $query.
                    "%' limit ".
                    $limit.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'inchikey') {
                $statement =
                    "select id, COUNT(*) OVER () from molecules where standard_inchi_key LIKE '%".
                    $query.
                    "%' limit ".
                    $limit.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'exact') {
                $statement =
                    "select id, COUNT(*) OVER () from mols where m@='".
                    $query.
                    "' limit ".
                    $limit.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'similarity') {
                $statement =
                    "select id, COUNT(*) OVER () from fps where mfp2%morganbv_fp('".
                    $query.
                    "') limit ".
                    $limit.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'tags') {
                if ($tagType == 'dataSource') {

                } else {
                    $results = Molecule::withAnyTags([$query], $tagType)->paginate($limit)->items();
                    $count = Molecule::withAnyTags([$query], $tagType)->count();
                }
            } elseif ($queryType == 'filters') {
                $orConditions = explode('OR', $query);
                $isORInitial = true;
                $statement =
                    'select molecule_id as id, COUNT(*) OVER () from properties where ';
                foreach ($orConditions as $orCondition) {
                    if ($isORInitial === false) {
                        $statement = $statement.' OR ';
                    }
                    $isORInitial = false;
                    $statement = $statement.'(';
                    $andConditions = explode(' ', trim($orCondition, ' '));
                    $isANDInitial = true;
                    foreach ($andConditions as $andCondition) {
                        if ($isANDInitial === false) {
                            $statement = $statement.' AND ';
                        }
                        $isANDInitial = false;
                        $_filter = explode(':', $andCondition);
                        if (str_contains($_filter[1], '..')) {
                            $range = array_values(explode('..', $_filter[1]));
                            $statement =
                                $statement.
                                '('.
                                $filterMap[$_filter[0]].
                                ' between '.
                                $range[0].
                                ' and '.
                                $range[1].
                                ')';
                        } elseif (
                            $_filter[1] === 'true' ||
                            $_filter[1] === 'false'
                        ) {
                            $statement =
                                $statement.
                                '('.
                                $filterMap[$_filter[0]].
                                ' = '.
                                $_filter[1].
                                ')';
                        } elseif (str_contains($_filter[1], '|')) {
                            $dbFilters = explode('|', $_filter[1]);
                            $dbs = explode('+', $dbFilters[0]);
                            $statement =
                                $statement.
                                '('.
                                $filterMap[$_filter[0]].
                                " @> '[\"".
                                implode('","', $dbs).
                                "\"]')";
                        } else {
                            if (str_contains($_filter[1], '+')) {
                                $_filter[1] = str_replace('+', ' ', $_filter[1]);
                            }
                            $statement =
                                $statement.
                                '('.$filterMap[$_filter[0]].'::TEXT ILIKE \'%'.$_filter[1].'%\')';
                        }
                    }
                    $statement = $statement.')';
                }
                $statement = $statement.' LIMIT '.$limit;
            } else {
                if ($query) {
                    $query = str_replace("'", "''", $query);
                    $statement =
                        "select id, COUNT(*) OVER () from molecules WHERE (\"name\"::TEXT ILIKE '%".
                        $query.
                        "%') OR (\"synonyms\"::TEXT ILIKE '%".
                        $query.
                        "%') OR (\"identifier\"::TEXT ILIKE '%".
                        $query.
                        "%') limit ".
                        $limit.
                        ' offset '.
                        $offset;
                } else {
                    $statement =
                        'select id, COUNT(*) OVER () from mols limit '.
                        $limit.
                        ' offset '.
                        $offset.
                        'ORDER BY annotation_level DESC';
                }
            }
            if ($statement) {
                $expression = DB::raw($statement);
                $qString = $expression->getValue(
                    DB::connection()->getQueryGrammar()
                );

                $hits = DB::select($qString);

                $count = count($hits) > 0 ? $hits[0]->count : 0;

                $ids = implode(
                    ',',
                    collect($hits)
                        ->pluck('id')
                        ->toArray()
                );

                if ($ids != '') {
                    $statement =
                        'SELECT * FROM molecules WHERE ID IN ('.
                        implode(
                            ',',
                            collect($hits)
                                ->pluck('id')
                                ->toArray()
                        ).
                        ')';
                    if ($sort == 'recent') {
                        $statement = $statement.' ORDER BY created_at DESC';
                    }
                    $expression = DB::raw($statement);
                    $string = $expression->getValue(
                        DB::connection()->getQueryGrammar()
                    );
                    $results = DB::select($string);
                } else {
                    $results = [];
                    $count = 0;
                }
            }
            $pagination = new LengthAwarePaginator(
                $results,
                $count,
                $limit,
                $page
            );

            return $pagination;
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
