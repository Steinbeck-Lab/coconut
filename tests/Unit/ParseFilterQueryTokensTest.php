<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ParseFilterQueryTokensTest extends TestCase
{
    private array $filterMap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterMap = getFilterMap();
    }

    public function test_parses_multi_word_class_value(): void
    {
        $tokens = parseFilterQueryTokens('class:Unsaturated hydrocarbons', $this->filterMap);

        $this->assertSame([['class', 'Unsaturated hydrocarbons']], $tokens);
    }

    public function test_parses_multi_word_superclass_value(): void
    {
        $tokens = parseFilterQueryTokens('superclass:Organometallic compounds', $this->filterMap);

        $this->assertSame([['superclass', 'Organometallic compounds']], $tokens);
    }

    public function test_parses_combined_and_filters(): void
    {
        $tokens = parseFilterQueryTokens('class:unsaturated-hydrocarbons mw:100..200', $this->filterMap);

        $this->assertCount(2, $tokens);
        $this->assertSame('class', $tokens[0][0]);
        $this->assertSame('unsaturated-hydrocarbons', $tokens[0][1]);
        $this->assertSame('mw', $tokens[1][0]);
        $this->assertSame('100..200', $tokens[1][1]);
    }

    public function test_parses_or_groups_separately(): void
    {
        $orConditions = explode('OR', 'tac:4..6 OR class:Acyl halides');

        $firstGroup = parseFilterQueryTokens(trim($orConditions[0]), $this->filterMap);
        $secondGroup = parseFilterQueryTokens(trim($orConditions[1]), $this->filterMap);

        $this->assertSame([['tac', '4..6']], $firstGroup);
        $this->assertSame([['class', 'Acyl halides']], $secondGroup);
    }

    public function test_preserves_ds_value_with_plus_and_pipe(): void
    {
        $tokens = parseFilterQueryTokens('ds:foo+bar|baz', $this->filterMap);

        $this->assertSame([['ds', 'foo+bar|baz']], $tokens);
    }

    public function test_prefers_longer_filter_keys(): void
    {
        $tokens = parseFilterQueryTokens('np_superclass:Terpenoids', $this->filterMap);

        $this->assertSame([['np_superclass', 'Terpenoids']], $tokens);
    }

    public function test_returns_empty_array_for_unknown_keys(): void
    {
        $tokens = parseFilterQueryTokens('hydrocarbons', $this->filterMap);

        $this->assertSame([], $tokens);
    }

    public function test_normalizes_text_filter_values_to_hyphenated_lowercase(): void
    {
        $this->assertSame('unsaturated-hydrocarbons', normalizeFilterTextValue('Unsaturated hydrocarbons'));
        $this->assertSame('unsaturated-hydrocarbons', normalizeFilterTextValue('Unsaturated+hydrocarbons'));
        $this->assertSame('acyl-halides', normalizeFilterTextValue('acyl-halides'));
        $this->assertSame('1,2-diaryl-2-propen-1-ols', normalizeFilterTextValue('1,2-diaryl-2-propen-1-ols'));
    }
}
