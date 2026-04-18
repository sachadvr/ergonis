<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\JsonPayloadParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class JsonPayloadParserTest extends TestCase
{
    public function testParseReturnsDecodedArray(): void
    {
        $request = Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"name":"JobPlanner","nested":{"enabled":true}}');

        $result = (new JsonPayloadParser())->parse($request);

        $this->assertSame('JobPlanner', $result['name']);
        $this->assertTrue($result['nested']['enabled']);
    }

    public function testParseReturnsEmptyArrayWhenContentIsNotString(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn(false);

        $this->assertSame([], (new JsonPayloadParser())->parse($request));
    }
}
