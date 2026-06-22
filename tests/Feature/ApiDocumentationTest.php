<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_generated_openapi_spec_preserves_numeric_response_status_codes(): void
    {
        $outputDirectory = storage_path('framework/testing');

        File::ensureDirectoryExists($outputDirectory);

        $outputPath = $outputDirectory.'/openapi.json';

        $this->artisan('rest:documentation', [
            '--path' => $outputDirectory,
        ])->assertSuccessful();

        $spec = json_decode(File::get($outputPath), true);

        $searchResponses = $spec['paths']['/api/search']['post']['responses'] ?? [];

        $this->assertArrayHasKey('500', $searchResponses);
        $this->assertArrayNotHasKey('0', $searchResponses);
        $this->assertArrayNotHasKey('"500"', $searchResponses);

        File::delete($outputPath);
    }

    public function test_api_documentation_page_loads(): void
    {
        $this->get('/api-documentation')->assertOk();
    }
}
