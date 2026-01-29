<?php

namespace App\Actions\Coconut;

use App\Models\Citation;
use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Organism;
use Illuminate\Database\QueryException;
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

    public $status = 'all';

    public $collection = null;

    public $organisms = null;

    public $citations = null;

    /**
     * Search based on given query.
     */
    public function query($query, $size, $type, $sort, $tagType, $page, $status = 'all')
    {
        // Decode the query to handle special characters like ® (%C2%AE) and + signs (spaces)
        // Always decode URL parameters as they may contain + for spaces or % encoded characters
        if ($query) {
            $this->query = urldecode($query);
        } else {
            $this->query = $query;
        }
        $this->size = $size;
        $this->type = $type;

        $this->sort = $sort;
        $this->tagType = $tagType;
        $this->page = $page;
        $this->status = $status;

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
        // Check for CNP identifier first
        if (strpos($query, 'CNP') === 0) {
            return 'identifier';
        }

        // Check for InChI format
        if (substr($query, 0, 6) == 'InChI=') {
            return 'inchi';
        }

        // Check for InChIKey (27 characters with dash at position 14)
        if (strlen($query) == 27 && substr($query, 14, 1) == '-' && preg_match('/^[A-Z0-9\-]+$/', $query)) {
            return 'inchikey';
        }

        // Check for partial InChIKey (14 uppercase characters)
        if (strlen($query) == 14 && preg_match('/^[A-Z]+$/', $query)) {
            return 'parttialinchikey';
        }

        // Check for molecular formula (must match pattern and not contain SMILES-specific characters)
        // Molecular formulas contain only element symbols and numbers (e.g., C6H12O6, H2O)
        if (preg_match('/^([A-Z][a-z]?\d*)+$/', $query) && ! preg_match('/[()@\[\]\/\\\\=#\-+]/', $query)) {
            return 'molecularformula';
        }

        // Check for SMILES (can contain special characters like @, [], (), etc.)
        if (preg_match('/^([^J][0-9BCOHNSOPrIFla@+\-\[\]\(\)\\\\\/%=#$]{6,})$/i', $query)) {
            return 'smiles';
        }

        // Check for general InChI pattern (without InChI= prefix)
        if (preg_match('/^[^J][0-9BCOHNSOPrIFla+\-\(\)\\\\\/,pqbtmsih]{6,}$/i', $query)) {
            return 'inchi';
        }

        return 'text';
    }

    /**
     * Apply status filter to SQL query.
     * Only adds a filter when status is 'approved' or 'revoked'.
     * When status is 'all', no filter is applied.
     */
    private function applyRawStatusFilter(&$sql, $tablePrefix = '')
    {
        $status = strtolower($this->status);
        $activeColumn = $tablePrefix ? "{$tablePrefix}.active" : 'active';

        if ($status === 'approved') {
            $sql .= " AND {$activeColumn} = TRUE";
        } elseif ($status === 'revoked') {
            $sql .= " AND {$activeColumn} = FALSE";
        }
        // When status is 'all', no filter is needed
    }

    /**
     * Build the SQL statement based on the query type.
     */
    private function buildStatement($queryType, $offset, $filterMap)
    {
        $sql = null;
        $params = [];
        $orderBy = null;

        switch ($queryType) {
            case 'smiles':
            case 'substructure':
                $sql = 'SELECT id, m, 
                    tanimoto_sml(morganbv_fp(mol_from_smiles(?)::mol), morganbv_fp(m::mol)) AS similarity, 
                    CASE WHEN m@=? THEN 1 ELSE 0 END AS is_exact_match,
                    COUNT(*) OVER () AS count 
                FROM mols 
                WHERE m@> mol_from_smiles(?)::mol';
                $orderBy = 'ORDER BY is_exact_match DESC, similarity DESC';
                $params = [$this->query, $this->query, $this->query];
                break;

            case 'inchi':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM molecules 
                          WHERE standard_inchi LIKE ?
                          AND NOT (is_parent = TRUE AND has_variants = TRUE)';
                $this->applyRawStatusFilter($sql);
                $orderBy = 'ORDER BY active DESC, annotation_level DESC';
                $params = ['%'.$this->query.'%'];
                break;

            case 'inchikey':
            case 'parttialinchikey':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM molecules 
                          WHERE standard_inchi_key LIKE ?
                          AND NOT (is_parent = TRUE AND has_variants = TRUE)';
                $this->applyRawStatusFilter($sql);
                $orderBy = 'ORDER BY active DESC, annotation_level DESC';
                $params = ['%'.$this->query.'%'];
                break;

            case 'molecularformula':
                $sql = 'SELECT molecules.id, COUNT(*) OVER () AS count
                          FROM molecules 
                          INNER JOIN properties ON molecules.id = properties.molecule_id
                          WHERE properties.molecular_formula = ?
                          AND NOT (molecules.is_parent = TRUE AND molecules.has_variants = TRUE)';
                $this->applyRawStatusFilter($sql, 'molecules');
                $orderBy = 'ORDER BY molecules.active DESC, molecules.annotation_level DESC';
                $params = [$this->query];
                break;

            case 'exact':
                // For stereo-aware exact matching:
                // Compare canonical SMILES representations generated by RDKit for both molecules
                // This ensures stereochemistry is preserved and aromatic forms are normalized
                $sql = 'WITH input_mol AS (
                            SELECT mol_from_smiles(?::cstring) AS m
                        )
                        SELECT mols.id, COUNT(*) OVER () AS count
                          FROM mols 
                          CROSS JOIN input_mol
                          WHERE mols.m@=input_mol.m
                          AND mol_to_smiles(mols.m)::text = mol_to_smiles(input_mol.m)::text';
                $params = [$this->query];
                break;

            case 'similarity':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                          FROM fps 
                          WHERE mfp2%morganbv_fp(?)';
                $orderBy = 'ORDER BY morganbv_fp(mol_from_smiles(?))<%>mfp2';
                $params = [$this->query, $this->query];
                break;

            case 'identifier':
                $sql = 'SELECT id, COUNT(*) OVER () AS count
                              FROM molecules 
                              WHERE ("identifier"::TEXT ILIKE ?)
                              AND NOT (is_parent = TRUE AND has_variants = TRUE)';
                $this->applyRawStatusFilter($sql);
                $orderBy = 'ORDER BY active DESC, annotation_level DESC';
                $params = ['%'.$this->query.'%'];
                break;

            case 'filters':
                return $this->buildFiltersStatement($filterMap);

            default:
                return $this->buildDefaultStatement($offset);
        }

        // Add ORDER BY if specified
        if ($orderBy) {
            $sql .= ' '.$orderBy;
        }

        // Add LIMIT and OFFSET at the end
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $this->size;
        $params[] = $offset;

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Apply status filter to Eloquent query.
     */
    private function applyStatusFilterToQuery($query)
    {
        $status = strtolower($this->status);
        if ($status === 'approved') {
            $query->where('active', true);
        } elseif ($status === 'revoked') {
            $query->where('active', false);
        }

        return $query;
    }

    /**
     * Build the SQL statement for 'tags' query type.
     */
    private function buildTagsStatement($offset)
    {
        if ($this->tagType == 'dataSource') {
            $this->collection = Collection::where('title', $this->query)->first();
            if ($this->collection) {
                $query = $this->collection->molecules()
                    ->whereIn('molecules.id', function ($query) {
                        $query->select('molecule_id')
                            ->from('entries')
                            ->where('collection_id', $this->collection->id)
                            ->distinct();
                    });

                $this->applyStatusFilterToQuery($query);

                return $query->orderBy('active', 'DESC')->orderBy('annotation_level', 'DESC')->paginate($this->size);
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

            // Use JSON-based subquery to avoid parameter limits and duplicate queries
            $organismIdsJson = json_encode($this->organisms->pluck('id')->toArray());

            $query = Molecule::whereHas('organisms', function ($query) use ($organismIdsJson) {
                $query->whereRaw('organism_id IN (SELECT value::bigint FROM json_array_elements_text(?::json))', [$organismIdsJson]);
            });

            $this->applyStatusFilterToQuery($query);

            return $query->where('is_parent', false)->orderBy('active', 'DESC')->orderBy('annotation_level', 'DESC')->paginate($this->size);
        } elseif ($this->tagType == 'citations') {
            $query_citations = array_map('strtolower', array_map('trim', explode(',', $this->query)));
            $this->citations = Citation::where(function ($query) use ($query_citations) {
                foreach ($query_citations as $name) {
                    $query->orWhereRaw('doi ILIKE ?', ['%'.$name.'%'])
                        ->orWhereRaw('title ILIKE ?', ['%'.$name.'%']);
                }
            })->get();

            // Use JSON-based subquery to avoid parameter limits and duplicate queries
            $citationIdsJson = json_encode($this->citations->pluck('id')->toArray());

            $query = Molecule::whereHas('citations', function ($query) use ($citationIdsJson) {
                $query->whereRaw('citation_id IN (SELECT value::bigint FROM json_array_elements_text(?::json))', [$citationIdsJson]);
            });

            $this->applyStatusFilterToQuery($query);

            return $query->where('is_parent', false)->orderBy('active', 'DESC')->orderBy('annotation_level', 'DESC')->paginate($this->size);
        } else {
            $query = Molecule::withAnyTags([$this->query], $this->tagType);

            $this->applyStatusFilterToQuery($query);

            return $query->where('is_parent', false)->orderBy('active', 'DESC')->paginate($this->size);
        }
    }

    /**
     * Build the SQL statement for 'filters' query type.
     */
    private function buildFiltersStatement($filterMap)
    {
        $orConditions = explode('OR', $this->query);
        $sql = 'SELECT properties.molecule_id as id, molecules.active, COUNT(*) OVER () AS count
                  FROM properties 
                  INNER JOIN molecules ON properties.molecule_id = molecules.id 
                  WHERE NOT (molecules.is_parent = TRUE AND molecules.has_variants = TRUE)';

        $status = strtolower($this->status);
        if ($status === 'approved') {
            $sql .= ' AND molecules.active = TRUE';
        } elseif ($status === 'revoked') {
            $sql .= ' AND molecules.active = FALSE';
        }

        $sql .= ' AND ';
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

        // Add ORDER BY to prioritize active molecules
        $sql .= ' ORDER BY molecules.active DESC';

        // Add LIMIT and OFFSET at the end
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $this->size;
        $params[] = (($this->page ?? 1) - 1) * $this->size;

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Build the default SQL statement.
     */
    private function buildDefaultStatement($offset)
    {
        $params = [];

        if ($this->query) {
            $sql = '
            SELECT id, COUNT(*) OVER () AS count
            FROM molecules 
            WHERE 
                (("name"::TEXT ILIKE ?) 
                OR ("synonyms"::TEXT ILIKE ?) 
                OR ("identifier"::TEXT ILIKE ?)) 
                AND is_parent = FALSE';

            $searchPattern = '%'.$this->query.'%';
            $exactPattern = $this->query;

            $params = [
                $searchPattern,
                $searchPattern,
                $searchPattern,  // WHERE clause
            ];

            // Apply status filter
            $this->applyRawStatusFilter($sql);

            $sql .= '
            ORDER BY 
                active DESC,
                CASE 
                    WHEN "name"::TEXT ILIKE ? THEN 1 
                    WHEN "synonyms"::TEXT ILIKE ? THEN 2 
                    WHEN "identifier"::TEXT ILIKE ? THEN 3 
                    WHEN "name"::TEXT ILIKE ? THEN 4 
                    WHEN "synonyms"::TEXT ILIKE ? THEN 5 
                    WHEN "identifier"::TEXT ILIKE ? THEN 6 
                    ELSE 7
                END';

            // Add ORDER BY params
            $params = array_merge($params, [
                $exactPattern,
                $exactPattern,
                $exactPattern,    // Exact matches in ORDER BY
                $searchPattern,
                $searchPattern,
                $searchPattern,  // Pattern matches in ORDER BY
            ]);
        } else {
            $sql = 'SELECT id, COUNT(*) OVER () AS count
                    FROM molecules 
                    WHERE (NOT (is_parent = TRUE AND has_variants = TRUE))';

            // Apply status filter
            $this->applyRawStatusFilter($sql);

            $sql .= '
                    ORDER BY active DESC, annotation_level DESC';
        }

        // Add LIMIT and OFFSET at the end
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $this->size;
        $params[] = $offset;

        return [
            'sql' => $sql,
            'params' => $params,
        ];
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
            // Use CTE (Common Table Expression) to avoid parameter limit
            $idsJson = json_encode($ids_array);

            $sql = '
            WITH id_list AS (
                SELECT value::bigint as id, row_number() OVER () as position
                FROM json_array_elements_text(?::json)
            )
            SELECT m.identifier, m.canonical_smiles, m.annotation_level, m.name, m.iupac_name, 
                   m.organism_count, m.citation_count, m.geo_count, m.collection_count, m.active
            FROM molecules m
            INNER JOIN id_list ON m.id = id_list.id
            WHERE NOT (m.is_parent = TRUE AND m.has_variants = TRUE)
            ORDER BY id_list.position';

            $params = [$idsJson];

            if ($this->sort == 'recent') {
                $sql .= ', m.created_at DESC';
            }

            $results = DB::select($sql, $params);

            return new LengthAwarePaginator($results, $count, $this->size, $this->page);
        } else {
            return new LengthAwarePaginator([], 0, $this->size, $this->page);
        }
    }
}
