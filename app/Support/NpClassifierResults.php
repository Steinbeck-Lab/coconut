<?php

namespace App\Support;

class NpClassifierResults
{
    /**
     * Map NP Classifier API / import payload keys to properties attributes.
     *
     * @return array<string, mixed>
     */
    public static function fromApiResponse(array $data): array
    {
        return [
            'np_classifier_pathway' => self::normalizeList($data['pathway_results'] ?? []),
            'np_classifier_superclass' => self::normalizeList($data['superclass_results'] ?? []),
            'np_classifier_class' => self::normalizeList($data['class_results'] ?? []),
            'np_classifier_is_glycoside' => (isset($data['isglycoside']) && $data['isglycoside'] !== '')
                ? filter_var($data['isglycoside'], FILTER_VALIDATE_BOOLEAN)
                : null,
        ];
    }

    /**
     * @param  array<int, string>|string|null  $values
     * @return array<int, string>|null
     */
    public static function normalizeList(array|string|null $values): ?array
    {
        if (is_string($values)) {
            $values = self::parseImportValue($values) ?? [];
        }

        if (! is_array($values)) {
            return null;
        }

        $filtered = array_values(array_filter(
            $values,
            static fn ($value) => is_string($value) && trim($value) !== ''
        ));

        return $filtered === [] ? null : $filtered;
    }

    /**
     * @return array<int, string>|null
     */
    public static function parseImportValue(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return self::normalizeList($decoded);
            }
        }

        return self::normalizeList([$trimmed]);
    }

    /**
     * Filter keys whose DB columns store string arrays as jsonb.
     *
     * @return array<string, string>
     */
    public static function jsonbArrayFilterColumns(): array
    {
        return [
            'np_pathway' => 'np_classifier_pathway',
            'np_superclass' => 'np_classifier_superclass',
            'np_class' => 'np_classifier_class',
        ];
    }

    /**
     * DB columns that store NP Classifier string lists as jsonb arrays.
     *
     * @return list<string>
     */
    public static function jsonbArrayPropertyColumns(): array
    {
        return array_values(self::jsonbArrayFilterColumns());
    }
}
