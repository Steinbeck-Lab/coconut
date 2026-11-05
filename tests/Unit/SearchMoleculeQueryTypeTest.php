<?php

namespace Tests\Unit;

use App\Actions\Coconut\SearchMolecule;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SearchMoleculeQueryTypeTest extends TestCase
{
    /**
     * @dataProvider queryTypeProvider
     */
    public function test_determine_query_type(string $query, string $expectedType): void
    {
        $search = new SearchMolecule;
        $method = new ReflectionMethod(SearchMolecule::class, 'determineQueryType');
        $method->setAccessible(true);

        $this->assertSame($expectedType, $method->invoke($search, $query));
    }

    public static function queryTypeProvider(): array
    {
        return [
            'cyclohexane smiles' => ['C1CCCCC1', 'smiles'],
            'benzene smiles' => ['c1ccccc1', 'smiles'],
            'molecular formula' => ['C6H12', 'molecularformula'],
            'glucose formula' => ['C6H12O6', 'molecularformula'],
            'water formula' => ['H2O', 'molecularformula'],
            'cnp identifier' => ['CNP0228556', 'identifier'],
            'compound name' => ['caffeine', 'text'],
        ];
    }
}
