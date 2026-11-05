<?php

namespace Tests\Unit;

use App\Support\NpClassifierResults;
use PHPUnit\Framework\TestCase;

class NpClassifierResultsTest extends TestCase
{
    public function test_from_api_response_stores_all_pathways(): void
    {
        $attributes = NpClassifierResults::fromApiResponse([
            'pathway_results' => ['Amino acids and Peptides', 'Polyketides'],
            'superclass_results' => ['Macrolides'],
            'class_results' => ['Epothilones', 'Lactones'],
            'isglycoside' => false,
        ]);

        $this->assertSame(['Amino acids and Peptides', 'Polyketides'], $attributes['np_classifier_pathway']);
        $this->assertSame(['Macrolides'], $attributes['np_classifier_superclass']);
        $this->assertSame(['Epothilones', 'Lactones'], $attributes['np_classifier_class']);
        $this->assertFalse($attributes['np_classifier_is_glycoside']);
    }

    public function test_from_api_response_returns_null_for_empty_lists(): void
    {
        $attributes = NpClassifierResults::fromApiResponse([
            'pathway_results' => [],
            'superclass_results' => [],
            'class_results' => [],
            'isglycoside' => '',
        ]);

        $this->assertNull($attributes['np_classifier_pathway']);
        $this->assertNull($attributes['np_classifier_superclass']);
        $this->assertNull($attributes['np_classifier_class']);
        $this->assertNull($attributes['np_classifier_is_glycoside']);
    }

    public function test_parse_import_value_decodes_json_array(): void
    {
        $parsed = NpClassifierResults::parseImportValue('["Amino acids and Peptides","Polyketides"]');

        $this->assertSame(['Amino acids and Peptides', 'Polyketides'], $parsed);
    }

    public function test_parse_import_value_wraps_scalar(): void
    {
        $parsed = NpClassifierResults::parseImportValue('Polyketides');

        $this->assertSame(['Polyketides'], $parsed);
    }
}
