<?php

namespace App\Actions\Coconut;

use App\Models\Citation;
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

    public $citations = null;

    /**
     * Search based on given query.
     */
    public function query($query, $size, $type, $sort, $tagType, $page)
    {
        $this->query = $query;
        $this->size = $size;
        $this->type = $type;

        $this->sort = $sort;
        $this->tagType = $tagType;
        $this->page = $page;

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

            $filterMap = getFilterMap();

            if ($queryType == 'tags') {
                $results = $this->buildTagsStatement($offset);
            } elseif ($queryType == 'filters') {
                $statement = $this->buildStatement($queryType, $offset, $filterMap);
                if ($statement) {
                    $results = $this->executeQuery($statement);
                }
            } else {
                $statement = $this->buildStatement($queryType, $offset, $filterMap);
                if ($statement) {
                    $results = $this->executeQuery($statement);
                }
            }

            return [$results,  $this->collection, $this->organisms, $this->citations];
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
            'inchikey' => '/^([0-9A-Z\-]{27})$/i',  // Modified to ensure exact length
            'parttialinchikey' => '/^([A-Z]{14})$/i',
            'smiles' => '/^([^J][0-9BCOHNSOPrIFla@+\-\[\]\(\)\\\\\/%=#$]{6,})$/i',
        ];

        if (strpos($query, 'CNP') === 0) {
            return 'identifier';
        }

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $query)) {
                if ($type == 'inchi' && substr($query, 0, 6) == 'InChI=') {
                    return 'inchi';
                } elseif ($type == 'inchikey' && substr($query, 14, 1) == '-' && strlen($query) == 27) {
                    return 'inchikey';
                } elseif ($type == 'parttialinchikey' && strlen($query) == 14) {
                    return 'parttialinchikey';
                } elseif ($type == 'smiles') {
                    return 'smiles';
                }
            }
        }

        return 'text';
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
                $statement = "SELECT id, m, 
                    tanimoto_sml(morganbv_fp(mol_from_smiles('{$this->query}')::mol), morganbv_fp(m::mol)) AS similarity, 
                    COUNT(*) OVER () AS count 
                FROM mols 
                WHERE m@> mol_from_smiles('{$this->query}')::mol 
                ORDER BY similarity DESC 
                LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'inchi':
                $statement = "SELECT id, COUNT(*) OVER () 
                          FROM molecules 
                          WHERE standard_inchi LIKE '%{$this->query}%' 
                          LIMIT {$this->size} OFFSET {$offset}";
                break;

            case 'inchikey':
            case 'parttialinchikey':
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
                return $this->collection->molecules()->where('active', true)->where('is_parent', false)->orderBy('annotation_level', 'desc')->paginate($this->size);
            } else {
                return [];
            }
        } elseif ($this->tagType == 'organisms') {
            $query_organisms = array_map('strtolower', array_map('trim', explode(',', $this->query)));
            $this->organisms = Organism::where(function ($query) use ($query_organisms) {
                foreach ($query_organisms as $name) {
                    $query->orWhereRaw('name ILIKE ?', ['%'.$name.'%']);
                }
            })->get();
            $organismIds = $this->organisms->pluck('id');

            return Molecule::whereHas('organisms', function ($query) use ($organismIds) {
                $query->whereIn('organism_id', $organismIds);
            })->where('active', true)->where('is_parent', false)->orderBy('annotation_level', 'DESC')->paginate($this->size);
        } elseif ($this->tagType == 'citations') {
            $query_citations = array_map('strtolower', array_map('trim', explode(',', $this->query)));
            $this->citations = Citation::where(function ($query) use ($query_citations) {
                foreach ($query_citations as $name) {
                    $query->orWhereRaw('doi ILIKE ?', ['%'.$name.'%'])
                        ->orWhereRaw('title ILIKE ?', ['%'.$name.'%']);
                }
            })->get();
            $citationIds = $this->citations->pluck('id');

            return Molecule::whereHas('citations', function ($query) use ($citationIds) {
                $query->whereIn('citation_id', $citationIds);
            })->where('active', true)->where('is_parent', false)->orderBy('annotation_level', 'DESC')->paginate($this->size);
        } else {
            return Molecule::withAnyTags([$this->query], $this->tagType)->where('active', true)->where('is_parent', false)->paginate($this->size);
        }
    }

    /**
     * Build the SQL statement for 'filters' query type.
     */
    private function buildFiltersStatement($filterMap)
    {
        $orConditions = explode('OR', $this->query);
        $statement = 'SELECT properties.molecule_id as id, COUNT(*) OVER () 
                  FROM properties 
                  INNER JOIN molecules ON properties.molecule_id = molecules.id 
                  WHERE molecules.active = TRUE 
                  AND NOT (molecules.is_parent = TRUE AND molecules.has_variants = TRUE) 
                  AND ';

        foreach ($orConditions as $outerIndex => $orCondition) {
            if ($outerIndex > 0) {
                $statement .= ' OR ';
            }

            $andConditions = explode(' ', trim($orCondition));
            $statement .= '(';

            foreach ($andConditions as $innerIndex => $andCondition) {
                if ($innerIndex > 0) {
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
                ((\"name\"::TEXT ILIKE '%{$this->query}%') 
                OR (\"synonyms\"::TEXT ILIKE '%{$this->query}%') 
                OR (\"identifier\"::TEXT ILIKE '%{$this->query}%')) 
                AND is_parent = FALSE 
                AND active = TRUE
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
                WHERE active = TRUE AND NOT (is_parent = TRUE AND has_variants = TRUE)
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
        $string = $expression->getValue(DB::connection()->getQueryGrammar());
        $hits = DB::select($string);
        $count = count($hits) > 0 ? $hits[0]->count : 0;

        $ids_array = collect($hits)->pluck('id')->toArray();
        $ids = implode(',', $ids_array);

        if ($ids != '') {

            $statement = "
            SELECT identifier, canonical_smiles, annotation_level, name, iupac_name, organism_count, citation_count, geo_count, collection_count
            FROM molecules
            WHERE id = ANY (array[{$ids}]) AND active = TRUE AND NOT (is_parent = TRUE AND has_variants = TRUE)
            ORDER BY array_position(array[{$ids}], id);
            ";

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
