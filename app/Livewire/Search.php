<?php

namespace App\Livewire;

use App\Http\Resources\MoleculeResource;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Search extends Component
{
    use WithPagination;

    #[Url(except: '', keep: true, history: true, as: 'q')]
    public $query = '';

    #[Url(as: 'limit')]
    public $size = 20;

    #[Url()]
    public $sort = null;

    #[Url()]
    public $page = null;

    #[Url()]
    public $type = null;

    #[Url()]
    public $tagType = null;

    public $collection = null;

    public function gotoPage($page)
    {
        $this->page = $page;
    }

    #[Layout('layouts.guest')]
    public function render()
    {

        try {
            set_time_limit(300);

            $queryType = 'text';
            $results = [];

            if ($this->query == '') {
                $this->type = '';
                $this->tagType = '';
            }

            $offset =
                (($this->page != null && $this->page != 'null' && $this->page != 0 ? $this->page : 1) -
                    1) *
                $this->size;

            if ($this->type) {
                $queryType = $this->type;
            } else {
                //inchi
                $re =
                    '/^((InChI=)?[^J][0-9BCOHNSOPrIFla+\-\(\)\\\\\/,pqbtmsih]{6,})$/i';
                preg_match_all($re, $this->query, $imatches, PREG_SET_ORDER, 0);

                if (count($imatches) > 0 && substr($this->query, 0, 6) == 'InChI=') {
                    $queryType = 'inchi';
                }

                //inchikey
                $re = '/^([0-9A-Z\-]+)$/i';
                preg_match_all($re, $this->query, $ikmatches, PREG_SET_ORDER, 0);
                if (
                    count($ikmatches) > 0 &&
                    substr($this->query, 14, 1) == '-' &&
                    strlen($this->query) == 27
                ) {
                    $queryType = 'inchikey';
                }

                // smiles
                $re = '/^([^J][0-9BCOHNSOPrIFla@+\-\[\]\(\)\\\\\/%=#$]{6,})$/i';
                preg_match_all($re, $this->query, $matches, PREG_SET_ORDER, 0);

                if (count($matches) > 0 && substr($this->query, 14, 1) != '-') {
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
                    $this->query.
                    "' limit ".
                    $this->size.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'inchi') {
                $statement =
                    "select id, COUNT(*) OVER () from molecules where standard_inchi LIKE '%".
                    $this->query.
                    "%' limit ".
                    $this->size.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'inchikey') {
                $statement =
                    "select id, COUNT(*) OVER () from molecules where standard_inchi_key LIKE '%".
                    $this->query.
                    "%' limit ".
                    $this->size.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'exact') {
                $statement =
                    "select id, COUNT(*) OVER () from mols where m@='".
                    $this->query.
                    "' limit ".
                    $this->size.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'similarity') {
                $statement =
                    "select id, COUNT(*) OVER () from fps where mfp2%morganbv_fp('".
                    $this->query.
                    "') limit ".
                    $this->size.
                    ' offset '.
                    $offset;
            } elseif ($queryType == 'tags') {
                if ($this->tagType == 'dataSource') {
                    $this->collection = Collection::where('title', $this->query)->first();
                    $results = $this->collection->molecules()->paginate($this->size);
                } else {
                    $results = Molecule::withAnyTags([$this->query], $this->tagType)->paginate($this->size);
                }

            } elseif ($queryType == 'filters') {
                $orConditions = explode('OR', $this->query);
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
                $statement = $statement.' LIMIT '.$this->size;
            } else {
                if ($this->query) {
                    $this->query = str_replace("'", "''", $this->query);
                    $statement =
                        "select id, COUNT(*) OVER () from molecules WHERE (\"name\"::TEXT ILIKE '%".
                        $this->query.
                        "%') OR (\"synonyms\"::TEXT ILIKE '%".
                        $this->query.
                        "%') OR (\"identifier\"::TEXT ILIKE '%".
                        $this->query.
                        "%') limit ".
                        $this->size.
                        ' offset '.
                        $offset;
                } else {
                    $statement =
                        'select id, COUNT(*) OVER () from mols limit '.
                        $this->size.
                        ' offset '.
                        $offset;
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
                    if ($this->sort == 'recent') {
                        $statement = $statement.' ORDER BY created_at DESC';
                    }
                    $expression = DB::raw($statement);
                    $string = $expression->getValue(
                        DB::connection()->getQueryGrammar()
                    );
                    $results = new LengthAwarePaginator(
                        DB::select($string),
                        $count,
                        $this->size,
                        $this->page
                    );
                } else {
                    $results = new LengthAwarePaginator(
                        [],
                        0,
                        $this->size,
                        $this->page
                    );
                }
            }

            return view('livewire.search', [
                'molecules' => $results,
            ]);
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
        // return view('livewire.search', [
        //     'molecules' => MoleculeResource::collection(
        //         Molecule::where('active', true)->orderByDesc('updated_at')->paginate($this->size)
        //     ),
        // ]);
    }
}
