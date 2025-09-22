<?php

namespace App\Actions\Coconut;

use App\Models\Citation;
use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Organism;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                $statementData = $this->buildStatement($queryType, $offset, $filterMap);
                if ($statementData) {
                    $results = $this->executeQuery($statementData);
                }
            } else {
                $statementData = $this->buildStatement($queryType, $offset, $filterMap);
                if ($statementData) {
                    $results = $this->executeQuery($statementData);
                }
            }

            return [$results,  $this->collection, $this->organisms, $this->citations];
        } catch (QueryException $exception) {
            // Re-throw the exception to be handled by the controller
            throw $exception;
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
        $sql = null;
        $params = [];

        switch ($queryType) {
            case 'smiles':
            case 'substructure':
                $sql = 'SELECT id, m, 
                    tanimoto_sml(morganbv_fp(mol_from_smiles(?)::mol), morganbv_fp(m::mol)) AS similarity, 
                    COUNT(*) OVER () AS count 
                FROM mols 
                WHERE m@> mol_from_smiles(?)::mol 
                ORDER BY similarity DESC 
                LIMIT ? OFFSET ?';
                $params = [$this->query, $this->query, $this->size, $offset];
                break;

            case 'inchi':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM molecules 
                          WHERE standard_inchi LIKE ? 
                          LIMIT ? OFFSET ?';
                $params = ['%'.$this->query.'%', $this->size, $offset];
                break;

            case 'inchikey':
            case 'parttialinchikey':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM molecules 
                          WHERE standard_inchi_key LIKE ? 
                          LIMIT ? OFFSET ?';
                $params = ['%'.$this->query.'%', $this->size, $offset];
                break;

            case 'exact':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM mols 
                          WHERE m@=? 
                          LIMIT ? OFFSET ?';
                $params = [$this->query, $this->size, $offset];
                break;

            case 'similarity':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM fps 
                          WHERE mfp2%morganbv_fp(?) 
                          ORDER BY morganbv_fp(mol_from_smiles(?))<%>mfp2 
                          LIMIT ? OFFSET ?';
                $params = [$this->query, $this->query, $this->size, $offset];
                break;

            case 'identifier':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                              FROM molecules 
                              WHERE ("identifier"::TEXT ILIKE ?) 
                              LIMIT ? OFFSET ?';
                $params = ['%'.$this->query.'%', $this->size, $offset];
                break;

            case 'filters':
                return $this->buildFiltersStatement($filterMap);

            default:
                return $this->buildDefaultStatement($offset);
        }

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Build the SQL statement for 'tags' query type.
     */
    private function buildTagsStatement($offset)
    {
        if ($this->tagType == 'dataSource') {
            $this->collection = Collection::where('title', $this->query)->first();
            if ($this->collection) {
                return $this->collection->molecules()
                    ->where('active', true)
                    ->where(function ($query) {
                        $query->where('is_parent', false)
                            ->orWhere(function ($subQuery) {
                                $subQuery->where('is_parent', true)
                                    ->where('has_variants', false);
                            });
                    })->paginate($this->size);
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
        $sql = 'SELECT properties.molecule_id as id, COUNT(*) OVER () AS count
                  FROM properties 
                  INNER JOIN molecules ON properties.molecule_id = molecules.id 
                  WHERE molecules.active = TRUE 
                  AND NOT (molecules.is_parent = TRUE AND molecules.has_variants = TRUE) 
                  AND ';
        $params = [];

        foreach ($orConditions as $outerIndex => $orCondition) {
            if ($outerIndex > 0) {
                $sql .= ' OR ';
            }

            $andConditions = explode(' ', trim($orCondition));
            $sql .= '(';

            foreach ($andConditions as $innerIndex => $andCondition) {
                if ($innerIndex > 0) {
                    $sql .= ' AND ';
                }

                [$filterKey, $filterValue] = explode(':', $andCondition);

                if (str_contains($filterValue, '..')) {
                    [$start, $end] = explode('..', $filterValue);
                    $sql .= "({$filterMap[$filterKey]} BETWEEN ? AND ?)";
                    $params[] = $start;
                    $params[] = $end;
                } elseif (in_array($filterValue, ['true', 'false'])) {
                    $sql .= "({$filterMap[$filterKey]} = ?)";
                    $params[] = $filterValue === 'true';
                } elseif (str_contains($filterValue, '|')) {
                    $dbFilters = explode('|', $filterValue);
                    $dbs = explode('+', $dbFilters[0]);
                    $sql .= "({$filterMap[$filterKey]} @> ?)";
                    $params[] = json_encode($dbs);
                } else {
                    $filterValue = str_replace('+', ' ', $filterValue);
                    $sql .= "(LOWER(REGEXP_REPLACE({$filterMap[$filterKey]}, '\\s+', '-', 'g'))::TEXT ILIKE ?)";
                    $params[] = '%'.$filterValue.'%';
                }
            }

            $sql .= ')';
        }

        $sql .= ' LIMIT ?';
        $params[] = $this->size;

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Build the default SQL statement.
     */
    private function buildDefaultStatement($offset)
    {
        if ($this->query) {
            $sql = '
            SELECT id, COUNT(*) OVER () AS count
            FROM molecules 
            WHERE 
                (("name"::TEXT ILIKE ?) 
                OR ("synonyms"::TEXT ILIKE ?) 
                OR ("identifier"::TEXT ILIKE ?)) 
                AND is_parent = FALSE 
                AND active = TRUE
            ORDER BY 
                CASE 
                    WHEN "name"::TEXT ILIKE ? THEN 1 
                    WHEN "synonyms"::TEXT ILIKE ? THEN 2 
                    WHEN "identifier"::TEXT ILIKE ? THEN 3 
                    WHEN "name"::TEXT ILIKE ? THEN 4 
                    WHEN "synonyms"::TEXT ILIKE ? THEN 5 
                    WHEN "identifier"::TEXT ILIKE ? THEN 6 
                    ELSE 7
                END
            LIMIT ? OFFSET ?';

            $searchPattern = '%'.$this->query.'%';
            $exactPattern = $this->query;

            return [
                'sql' => $sql,
                'params' => [
                    $searchPattern,
                    $searchPattern,
                    $searchPattern,  // WHERE clause
                    $exactPattern,
                    $exactPattern,
                    $exactPattern,    // Exact matches in ORDER BY
                    $searchPattern,
                    $searchPattern,
                    $searchPattern,  // Pattern matches in ORDER BY
                    $this->size,
                    $offset,
                ],
            ];
        } else {
            return [
                'sql' => 'SELECT id, COUNT(*) OVER () AS count
                    FROM molecules 
                    WHERE active = TRUE AND NOT (is_parent = TRUE AND has_variants = TRUE)
                    ORDER BY annotation_level DESC 
                    LIMIT ? OFFSET ?',
                'params' => [$this->size, $offset],
            ];
        }
    }

    /**
     * Execute the given SQL statement and return the results.
     */
    private function executeQuery($statementData)
    {
        // Execute parameterized query
        $hits = DB::select($statementData['sql'], $statementData['params']);

        $count = count($hits) > 0 ? $hits[0]->count : 0;

        $ids_array = collect($hits)->pluck('id')->toArray();

        if (! empty($ids_array)) {
            $placeholders = str_repeat('?,', count($ids_array) - 1).'?';

            $sql = "
            SELECT identifier, canonical_smiles, annotation_level, name, iupac_name, organism_count, citation_count, geo_count, collection_count
            FROM molecules
            WHERE id = ANY (array[{$placeholders}]::bigint[]) AND active = TRUE AND NOT (is_parent = TRUE AND has_variants = TRUE)
            ORDER BY array_position(array[{$placeholders}]::bigint[], id)";

            $params = array_merge($ids_array, $ids_array);

            if ($this->sort == 'recent') {
                $sql .= ' ORDER BY created_at DESC';
            }

            $results = DB::select($sql, $params);

            return new LengthAwarePaginator($results, $count, $this->size, $this->page);
        } else {
            return new LengthAwarePaginator([], 0, $this->size, $this->page);
        }
    }

    /**
     * Handle exceptions by returning a user-friendly error response.
     */
    private function handleException(QueryException $exception)
    {
        $message = $exception->getMessage();

        // Log the exception for debugging
        Log::error('SearchMolecule query exception', [
            'query' => $this->query,
            'type' => $this->type,
            'exception_message' => $message,
            'exception_code' => $exception->getCode(),
        ]);

        if (str_contains(strtolower($message), 'sqlstate[42p01]')) {
            Log::error('It appears that the molecules table is not indexed. To enable search, please index molecules table and generate corresponding fingerprints.');

            return [
                'error' => true,
                'message' => 'Indexing issue. Plese report this to info.COCONUT@uni-jena.de',
                'results' => [],
                'total' => 0,
            ];
        }

        if (str_contains(strtolower($message), 'sqlstate[22000]')) {
            return [
                'error' => true,
                'message' => 'Error processing the molecule.',
                'results' => [],
                'total' => 0,
            ];
        }

        return [
            'error' => true,
            'message' => 'An error occurred while searching. Please try again.',
            'results' => [],
            'total' => 0,
        ];
    }
}
