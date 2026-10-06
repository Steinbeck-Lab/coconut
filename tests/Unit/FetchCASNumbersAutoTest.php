<?php

namespace Tests\Unit;

use App\Console\Commands\SubmissionsAutoProcess\FetchCASNumbersAuto;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchCASNumbersAutoTest extends TestCase
{
    private function command(): FetchCASNumbersAuto
    {
        config([
            'services.cas.base_url' => 'https://commonchemistry.cas.org/direct-api',
            'services.cas.origin' => 'https://commonchemistry.cas.org/api-overview',
            'services.cas.cas_key' => 'secret-key',
        ]);

        $command = new FetchCASNumbersAuto;
        $property = new \ReflectionProperty($command, 'apiBaseUrl');
        $property->setValue($command, config('services.cas.base_url'));

        return $command;
    }

    private function invoke(FetchCASNumbersAuto $command, string $method, array $args)
    {
        return (new \ReflectionMethod($command, $method))->invoke($command, ...$args);
    }

    public function test_detail_request_uses_direct_api_and_sends_x_origin_header(): void
    {
        Http::fake([
            'commonchemistry.cas.org/direct-api/detail*' => Http::response(['rn' => '58-08-2', 'name' => 'Caffeine']),
        ]);

        $details = $this->invoke($this->command(), 'fetchCASFromCommonChemistryAPI', ['58-08-2', 'detail']);

        $this->assertIsArray($details);
        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://commonchemistry.cas.org/direct-api/detail?cas_rn=58-08-2')
                && $request->header('x-origin') === ['https://commonchemistry.cas.org/api-overview']
                && $request->header('X-API-KEY') === ['secret-key'];
        });
    }

    public function test_search_returns_all_matches_not_just_the_first(): void
    {
        Http::fake([
            'commonchemistry.cas.org/direct-api/search*' => Http::response([
                'count' => 2,
                'results' => [['rn' => '26351-04-2'], ['rn' => '58-08-2']],
            ]),
        ]);

        $result = $this->invoke($this->command(), 'searchCASNumbers', ['CN1C=NC2=C1C(=O)N(C(=O)N2C)C']);

        $this->assertSame(['26351-04-2', '58-08-2'], $result);
        Http::assertSent(fn (Request $request) => $request->header('x-origin') === ['https://commonchemistry.cas.org/api-overview']);
    }

    public function test_failed_request_logs_status_and_body_but_not_the_api_key(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);
        $logged = [];
        Log::shouldReceive('warning')->andReturnUsing(function ($message) use (&$logged) {
            $logged[] = $message;
        });
        Log::shouldReceive('info')->andReturnNull();

        $command = $this->command();
        $result = $this->invoke($command, 'doActualRequest', ['https://commonchemistry.cas.org/direct-api/detail', ['cas_rn' => '58-08-2']]);

        $this->assertNull($result);
        $all = implode("\n", $logged);
        $this->assertStringContainsString('Status: 401', $all);
        $this->assertStringContainsString('Unauthorized', $all);
        $this->assertStringNotContainsString('secret-key', $all);
    }
}
