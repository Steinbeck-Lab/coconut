<?php

namespace Tests\Unit;

use App\Services\GeoLocationCountryExtractor;
use Tests\TestCase;

class GeoLocationCountryExtractorTest extends TestCase
{
    private GeoLocationCountryExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new GeoLocationCountryExtractor;
    }

    public function test_extracts_country_from_last_comma_segment(): void
    {
        $result = $this->extractor->extract('Hiroshima Bay, Japan');

        $this->assertNotNull($result);
        $this->assertSame('Japan', $result->country);
        $this->assertSame('JP', $result->countryCode);
        $this->assertSame('segment', $result->method);
    }

    public function test_extracts_country_from_india_segment(): void
    {
        $result = $this->extractor->extract('Tamil Nadu, India');

        $this->assertNotNull($result);
        $this->assertSame('India', $result->country);
        $this->assertSame('IN', $result->countryCode);
    }

    public function test_extracts_usa_alias_with_county(): void
    {
        $result = $this->extractor->extract('Carmel-by-the-Sea, California, USA');

        $this->assertNotNull($result);
        $this->assertSame('United States', $result->country);
        $this->assertSame('US', $result->countryCode);
        $this->assertSame('California', $result->county);
    }

    public function test_extracts_mexico_from_parentheses_state(): void
    {
        $result = $this->extractor->extract('Tepeaca(Puebla)');

        $this->assertNotNull($result);
        $this->assertSame('Mexico', $result->country);
        $this->assertSame('MX', $result->countryCode);
        $this->assertSame('parens_mx', $result->method);
        $this->assertSame('Tepeaca', $result->county);
    }

    public function test_extracts_whole_name_country(): void
    {
        $result = $this->extractor->extract('Peru');

        $this->assertNotNull($result);
        $this->assertSame('Peru', $result->country);
        $this->assertSame('PE', $result->countryCode);
        $this->assertSame('segment', $result->method);
    }

    public function test_returns_null_for_marine_region_without_country(): void
    {
        $this->assertNull($this->extractor->extract('Tyrrhenian Sea'));
    }

    public function test_should_skip_geocoding_for_placeholder_names(): void
    {
        $this->assertTrue($this->extractor->shouldSkipGeocoding('Not specified(Ciudad de Mexico)'));
        $this->assertTrue($this->extractor->shouldSkipGeocoding('No especified(No especified)'));
        $this->assertFalse($this->extractor->shouldSkipGeocoding('Tyrrhenian Sea'));
    }
}
