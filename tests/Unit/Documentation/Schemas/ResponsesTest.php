<?php

namespace Tests\Unit\Documentation\Schemas;

use App\Documentation\Schemas\Responses;
use Lomkit\Rest\Documentation\Schemas\Response;
use PHPUnit\Framework\TestCase;

class ResponsesTest extends TestCase
{
    public function test_with_others_preserves_numeric_http_status_code_keys(): void
    {
        $responses = (new Responses)
            ->withDefault((new Response)->withDescription('OK'))
            ->withOthers([
                '500' => (new Response)->withDescription('Server Error'),
                '401' => (new Response)->withDescription('Unauthorized'),
            ]);

        $this->assertArrayHasKey('500', $responses->others());
        $this->assertArrayHasKey('401', $responses->others());
        $this->assertSame('Server Error', $responses->others()['500']->description());
    }

    public function test_json_serialize_preserves_numeric_http_status_code_keys(): void
    {
        $serialized = (new Responses)
            ->withDefault((new Response)->withDescription('OK'))
            ->withOthers([
                '500' => (new Response)->withDescription('Server Error'),
            ])
            ->jsonSerialize();

        $this->assertArrayHasKey('default', $serialized);
        $this->assertArrayHasKey('500', $serialized);
        $this->assertArrayNotHasKey(0, $serialized);
        $this->assertSame('Server Error', $serialized['500']['description']);
    }
}
