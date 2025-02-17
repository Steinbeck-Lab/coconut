<?php

namespace App\Rest\Query;

use Lomkit\Rest\Query\Builder as BaseBuilder;

class CustomBuilder extends BaseBuilder
{
    public function filter($field, $operator, $value, $type = 'and', $nested = null)
    {
        // Handle nested filters
        if ($nested !== null) {
            return $this->queryBuilder->where(function ($query) use ($nested) {
                $this->newQueryBuilder(['resource' => $this->resource, 'query' => $query])
                    ->applyFilters($nested);
            }, null, null, $type);
        }

        // Convert 'ilike' to case-insensitive 'like' for PostgreSQL
        if ($operator === 'ilike') {
            $field = $this->queryBuilder->getModel()->getTable().'.'.$field;

            return $this->queryBuilder->whereRaw("LOWER($field) LIKE ?", [strtolower($value)], $type);
        }

        // Call parent implementation for other operators
        return parent::filter($field, $operator, $value, $type, $nested);
    }
}
