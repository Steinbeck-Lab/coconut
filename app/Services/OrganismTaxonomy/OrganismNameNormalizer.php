<?php

namespace App\Services\OrganismTaxonomy;

class OrganismNameNormalizer
{
    public function normalizeForLookup(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '';
        }

        $words[0] = mb_convert_case(mb_strtolower($words[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        for ($i = 1, $count = count($words); $i < $count; $i++) {
            $words[$i] = mb_strtolower($words[$i], 'UTF-8');
        }

        return implode(' ', $words);
    }

    public function firstToken(string $name): ?string
    {
        $normalized = $this->normalizeForLookup($name);
        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $words[0] ?? null;
    }

    public function equalsForLookup(string $left, string $right): bool
    {
        return $this->normalizeForLookup($left) === $this->normalizeForLookup($right);
    }
}
