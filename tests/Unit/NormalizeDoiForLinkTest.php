<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NormalizeDoiForLinkTest extends TestCase
{
    #[DataProvider('validDoiProvider')]
    public function test_normalizes_valid_doi_inputs(string $input, string $expectedLabel, string $expectedUrl): void
    {
        $result = normalizeDoiForLink($input);

        $this->assertNotNull($result);
        $this->assertSame($expectedLabel, $result['label']);
        $this->assertSame($expectedUrl, $result['url']);
    }

    public static function validDoiProvider(): array
    {
        $bareDoi = '10.1016/j.tetlet.2011.07.013';

        return [
            'bare doi' => [$bareDoi, $bareDoi, 'https://doi.org/'.$bareDoi],
            'https url' => ['https://doi.org/'.$bareDoi, $bareDoi, 'https://doi.org/'.$bareDoi],
            'http url' => ['http://doi.org/'.$bareDoi, $bareDoi, 'https://doi.org/'.$bareDoi],
            'doi prefix' => ['doi:'.$bareDoi, $bareDoi, 'https://doi.org/'.$bareDoi],
            'dx.doi.org url' => ['https://dx.doi.org/'.$bareDoi, $bareDoi, 'https://doi.org/'.$bareDoi],
        ];
    }

    #[DataProvider('invalidDoiProvider')]
    public function test_returns_null_for_invalid_doi_inputs(string $input): void
    {
        $this->assertNull(normalizeDoiForLink($input));
    }

    public static function invalidDoiProvider(): array
    {
        return [
            'empty string' => [''],
            'whitespace only' => ['   '],
            'not a doi' => ['not-a-doi'],
        ];
    }
}
