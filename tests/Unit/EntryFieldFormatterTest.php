<?php

namespace Tests\Unit;

use App\Services\EntryFieldFormatter;
use Tests\TestCase;

class EntryFieldFormatterTest extends TestCase
{
    public function test_format_returns_empty_for_null_or_blank(): void
    {
        $this->assertSame([], EntryFieldFormatter::format(null));
        $this->assertSame([], EntryFieldFormatter::format(''));
        $this->assertSame([], EntryFieldFormatter::format('   '));
    }

    public function test_format_single_value_without_delimiters(): void
    {
        $this->assertSame(
            [['values' => ['Homo sapiens']]],
            EntryFieldFormatter::format('Homo sapiens')
        );
    }

    public function test_format_splits_pipe_within_group_and_hash_between_groups(): void
    {
        $this->assertSame(
            [
                ['values' => ['a', 'b']],
                ['values' => ['c']],
            ],
            EntryFieldFormatter::format('a|b##c')
        );
    }

    public function test_format_location_splits_semicolon_and_pipe(): void
    {
        $this->assertSame(
            [
                ['values' => ['forest', 'river']],
                ['values' => ['desert']],
            ],
            EntryFieldFormatter::formatLocation('forest;river##desert')
        );
    }

    public function test_to_html_returns_dash_for_empty_groups(): void
    {
        $this->assertSame('-', EntryFieldFormatter::toHtml([]));
    }

    public function test_to_html_renders_doi_links(): void
    {
        $html = EntryFieldFormatter::toHtml(
            [['values' => ['10.1234/example']]],
            'https://doi.org/'
        );

        $this->assertStringContainsString('href="https://doi.org/10.1234/example"', $html);
        $this->assertStringContainsString('10.1234/example', $html);
    }

    public function test_to_html_escapes_malicious_content(): void
    {
        $html = EntryFieldFormatter::toHtml(
            [['values' => ['<script>alert(1)</script>']]],
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_to_html_renders_multiple_groups(): void
    {
        $html = EntryFieldFormatter::toHtml(
            EntryFieldFormatter::format('a|b##c')
        );

        $this->assertStringContainsString('Group 1:', $html);
        $this->assertStringContainsString('Group 2:', $html);
        $this->assertStringNotContainsString('##', $html);
    }

    public function test_to_html_renders_url_links_when_enabled(): void
    {
        $html = EntryFieldFormatter::toHtml(
            [['values' => ['https://example.org/page']]],
            null,
            true
        );

        $this->assertStringContainsString('href="https://example.org/page"', $html);
    }
}
