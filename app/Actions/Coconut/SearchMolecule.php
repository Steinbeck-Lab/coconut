<?php

namespace App\Actions\Coconut;

use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Organism;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchMolecule
{
    public $query = '';

    public $size = 20;

    public $sort = null;

    public $page = null;

    public $tagType = null;

    public $type = null;

    public $collection = null;

    public $organisms = null;

    /**
     * Search based on given query.
     */
    public function query($query, $size, $type, $sort, $tagType)
    {
        $this->query = $query;
        $this->size = $size;
        $this->type = $type;
        $this->sort = $sort;
        $this->tagType = $tagType;

        try {
            set_time_limit(300);

            $queryType = 'text';
            $results = [];

            if ($this->query == '') {
                $this->type = '';
                $this->tagType = '';
            }

            $offset = (($this->page ?? 1) - 1) * $this->size;

            if ($this->type) {
                $queryType = $this->type;
            } else {
                $queryType = $this->determineQueryType($this->query);
            }
            $queryType = strtolower($queryType);

            $filterMap = $this->getFilterMap();

            if ($queryType == 'tags') {
                $results = $this->buildTagsStatement($offset);
            } else {
                $statement = $this->buildStatement($queryType, $offset, $filterMap);

                if ($statement) {
                    $results = $this->executeQuery($statement);
                }
            }

            return [$results,  $this->collection];

        } catch (QueryException $exception) {

            return $this->handleException($exception);

        }
    }

    /**
     * Determine the query type based on the query pattern.
     */
    private function determineQueryType($query)
    {
        $patterns = [
            'inchi' => '/^((InChI=)?[^J][0-9BCOHNSOPrIFla+\-\(\)\\\\\/,pqbtmsih]{6,})$/i',
            'inchikey' => '/^([0-9A-Z\-]+)$/i',
            'smiles' => '/^([^J][0-9BCOHNSOPrIFla@+\-\[\]\(\)\\\\\/%=#$]{6,})$/i',
        ];

        if (strpos($query, 'CNP') === 0) {
            return 'identifier';
        }

        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER, 0)) {
                if ($type == 'inchi' && substr($query, 0, 6) == 'InChI=') {
                    return 'inchi';
                } elseif ($type == 'inchikey' && substr($query, 14, 1) == '-' && strlen($query) == 27) {
                    return 'inchikey';
                } elseif ($type == 'smiles' && substr($query, 14, 1) != '-') {
                    return 'smiles';
                }
            }
        }

        return 'text';
    }

    /**
     * Return a mapping of filter codes to database columns.
     */
    private function getFilterMap()
    {
        return [
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
    }

    /**
     * Build the SQL statement based on the query type.
     */
    private function buildStatement($queryType, $offset, $filterMap)
    {
        $statement = null;

        switch ($queryType) {
            case 'smiles':
            case 'substructure':
                $statement = "SELECT id, COUNT(*) OVER () 
                          FROM mols 
                          WHERE m@>'{$this->query}' 
                          LIMIT {$this->size} OFFSET {$offset}";

                break;

            case 'inchi':
                $statement = "SELECT id, COUNT(*) OVER () 
                          FROM molecules 
                          WHERE standard_inchi LIKE '%{$this->query}%' 
                          LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'inchikey':
                $statement = "SELECT id, COUNT(*) OVER () 
                          FROM molecules 
                          WHERE standard_inchi_key LIKE '%{$this->query}%' 
                          LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'exact':
                $statement = "SELECT id, COUNT(*) OVER () 
                          FROM mols 
                          WHERE m@='{$this->query}' 
                          LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'similarity':
                $statement = "SELECT id, COUNT(*) OVER () 
                          FROM fps 
                          WHERE mfp2%morganbv_fp('{$this->query}') 
                          ORDER BY morganbv_fp(mol_from_smiles('{$this->query}'))<%>mfp2 
                          LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'identifier':
                $statement = "SELECT id, COUNT(*) OVER () 
                              FROM molecules 
                              WHERE (\"identifier\"::TEXT ILIKE '%{$this->query}%') 
                              LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'filters':
                $statement = $this->buildFiltersStatement($filterMap);
                break;

            default:
                $statement = $this->buildDefaultStatement($offset);
                break;
        }

        return $statement;
    }

    /**
     * Build the SQL statement for 'tags' query type.
     */
    private function buildTagsStatement($offset)
    {
        if ($this->tagType == 'dataSource') {
            $this->collection = Collection::where('title', $this->query)->first();
            if ($this->collection) {
                return $this->collection->molecules()->orderBy('annotation_level', 'desc')->paginate($this->size);
            } else {
                return [];
            }
        } elseif ($this->tagType == 'organisms') {
            $this->organisms = array_map('strtolower', array_map('trim', explode(',', $this->query)));
            $organismIds = Organism::where(function ($query) {
                foreach ($this->organisms as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [$name]);
                }
            })->pluck('id');

            return Molecule::whereHas('organisms', function ($query) use ($organismIds) {
                $query->whereIn('organism_id', $organismIds);
            })->orderBy('annotation_level', 'DESC')->paginate($this->size);
        } else {
            return Molecule::withAnyTags([$this->query], $this->tagType)->paginate($this->size);
        }
    }

    /**
     * Build the SQL statement for 'filters' query type.
     */
    private function buildFiltersStatement($filterMap)
    {
        $orConditions = explode('OR', $this->query);
        $statement = 'SELECT molecule_id as id, COUNT(*) OVER () 
                  FROM properties WHERE ';

        foreach ($orConditions as $index => $orCondition) {
            if ($index > 0) {
                $statement .= ' OR ';
            }

            $andConditions = explode(' ', trim($orCondition));
            $statement .= '(';

            foreach ($andConditions as $index => $andCondition) {
                if ($index > 0) {
                    $statement .= ' AND ';
                }

                [$filterKey, $filterValue] = explode(':', $andCondition);

                if (str_contains($filterValue, '..')) {
                    [$start, $end] = explode('..', $filterValue);
                    $statement .= "({$filterMap[$filterKey]} BETWEEN {$start} AND {$end})";
                } elseif (in_array($filterValue, ['true', 'false'])) {
                    $statement .= "({$filterMap[$filterKey]} = {$filterValue})";
                } elseif (str_contains($filterValue, '|')) {
                    $dbFilters = explode('|', $filterValue);
                    $dbs = explode('+', $dbFilters[0]);
                    $statement .= "({$filterMap[$filterKey]} @> '[\"".implode('","', $dbs)."\"]')";
                } else {
                    $filterValue = str_replace('+', ' ', $filterValue);
                    $statement .= "(LOWER(REGEXP_REPLACE({$filterMap[$filterKey]}, '\\s+', '-', 'g'))::TEXT ILIKE '%{$filterValue}%')";
                }
            }

            $statement .= ')';
        }

        return "{$statement} LIMIT {$this->size}";
    }

    /**
     * Build the default SQL statement.
     */
    private function buildDefaultStatement($offset)
    {
        if ($this->query) {
            $this->query = str_replace("'", "''", $this->query);

            return "
            SELECT id, COUNT(*) OVER () 
            FROM molecules 
            WHERE 
                (\"name\"::TEXT ILIKE '%{$this->query}%') 
                OR (\"synonyms\"::TEXT ILIKE '%{$this->query}%') 
                OR (\"identifier\"::TEXT ILIKE '%{$this->query}%') 
            ORDER BY 
                CASE 
                    WHEN \"name\"::TEXT ILIKE '{$this->query}' THEN 1 
                    WHEN \"synonyms\"::TEXT ILIKE '{$this->query}' THEN 2 
                    WHEN \"identifier\"::TEXT ILIKE '{$this->query}' THEN 3 
                    WHEN \"name\"::TEXT ILIKE '%{$this->query}%' THEN 4 
                    WHEN \"synonyms\"::TEXT ILIKE '%{$this->query}%' THEN 5 
                    WHEN \"identifier\"::TEXT ILIKE '%{$this->query}%' THEN 6 
                    ELSE 7 
                END
            LIMIT {$this->size} OFFSET {$offset}";
        } else {
            return "SELECT id, COUNT(*) OVER () 
                FROM molecules 
                ORDER BY annotation_level DESC 
                LIMIT {$this->size} OFFSET {$offset}";
        }
    }

    /**
     * Execute the given SQL statement and return the results.
     */
    private function executeQuery($statement)
    {
        $expression = DB::raw($statement);
        $hits = $expression->getValue(DB::connection()->getQueryGrammar());
        $count = count($hits) > 0 ? $hits[0]->count : 0;

        $ids = implode(',', collect($hits)->pluck('id')->toArray());

        if ($ids != '') {
            $statement = "SELECT * FROM molecules WHERE ID IN ({$ids})";
            if ($this->sort == 'recent') {
                $statement .= ' ORDER BY created_at DESC';
            }
            $expression = DB::raw($statement);
            $string = $expression->getValue(DB::connection()->getQueryGrammar());

            return new LengthAwarePaginator(DB::select($string), $count, $this->size, $this->page);
        } else {
            return new LengthAwarePaginator([], 0, $this->size, $this->page);
        }
    }

    /**
     * Handle exceptions by returning a proper JSON response.
     */
    private function handleException(QueryException $exception)
    {
        $message = $exception->getMessage();
        if (str_contains(strtolower($message), 'sqlstate[42p01]')) {
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