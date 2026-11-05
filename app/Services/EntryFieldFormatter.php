<?php

namespace App\Services;

use Illuminate\Support\HtmlString;

class EntryFieldFormatter
{
    /**
     * @return array<int, array{values: array<int, string>}>
     */
    public static function format(?string $value, string $innerSeparator = '|'): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $groups = array_map('trim', explode('##', $value));
        $result = [];

        foreach ($groups as $group) {
            if ($group === '') {
                continue;
            }

            $values = array_values(array_filter(
                array_map('trim', explode($innerSeparator, $group)),
                fn (string $part) => $part !== ''
            ));

            if ($values !== []) {
                $result[] = ['values' => $values];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array{values: array<int, string>}>
     */
    public static function formatLocation(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $groups = array_map('trim', explode('##', $value));
        $result = [];

        foreach ($groups as $group) {
            if ($group === '') {
                continue;
            }

            $values = array_values(array_filter(
                array_map('trim', preg_split('/[|;]/', $group) ?: []),
                fn (string $part) => $part !== ''
            ));

            if ($values !== []) {
                $result[] = ['values' => $values];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array{values: array<int, string>}>  $groups
     */
    public static function isEmpty(array $groups): bool
    {
        return $groups === [];
    }

    public static function toHtmlString(?string $value, ?string $linkPrefix = null, bool $linkAsUrl = false): HtmlString
    {
        return new HtmlString(self::toHtml(self::format($value), $linkPrefix, $linkAsUrl));
    }

    public static function locationToHtmlString(?string $value): HtmlString
    {
        return new HtmlString(self::toHtml(self::formatLocation($value)));
    }

    /**
     * @param  array<int, array{values: array<int, string>}>  $groups
     */
    public static function toHtml(array $groups, ?string $linkPrefix = null, bool $linkAsUrl = false): string
    {
        if (self::isEmpty($groups)) {
            return '-';
        }

        if (count($groups) === 1) {
            return self::renderValueList($groups[0]['values'], $linkPrefix, $linkAsUrl);
        }

        $html = '<ul class="list-none space-y-2 m-0 p-0">';
        foreach ($groups as $index => $group) {
            $html .= '<li><span class="font-medium text-gray-700">Group '.($index + 1).':</span> ';
            $html .= self::renderValueList($group['values'], $linkPrefix, $linkAsUrl);
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param  array<int, string>  $values
     */
    private static function renderValueList(array $values, ?string $linkPrefix, bool $linkAsUrl): string
    {
        $items = [];
        foreach ($values as $value) {
            $items[] = self::renderValue($value, $linkPrefix, $linkAsUrl);
        }

        return implode(', ', $items);
    }

    private static function renderValue(string $value, ?string $linkPrefix, bool $linkAsUrl): string
    {
        $escaped = e($value);

        if ($linkPrefix !== null) {
            $href = e($linkPrefix.$value);

            return '<a href="'.$href.'" class="text-primary-600 hover:underline break-all" target="_blank" rel="noopener noreferrer">'.$escaped.'</a>';
        }

        if ($linkAsUrl && self::looksLikeUrl($value)) {
            $href = e($value);

            return '<a href="'.$href.'" class="text-primary-600 hover:underline break-all" target="_blank" rel="noopener noreferrer">'.$escaped.'</a>';
        }

        return '<span class="break-all">'.$escaped.'</span>';
    }

    private static function looksLikeUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
