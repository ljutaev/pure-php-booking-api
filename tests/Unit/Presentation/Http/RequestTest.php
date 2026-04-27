<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http;

use App\Presentation\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testCreateBuildsRequestWithMethodAndUri(): void
    {
        $request = Request::create('GET', '/api/v1/health');

        self::assertSame('GET', $request->method);
        self::assertSame('/api/v1/health', $request->uri);
    }

    public function testCreateNormalizesMethodToUpperCase(): void
    {
        $request = Request::create('get', '/api/v1/health');

        self::assertSame('GET', $request->method);
    }

    public function testCreateStripsQueryStringFromUri(): void
    {
        $request = Request::create('GET', '/api/v1/hotels?page=2');

        self::assertSame('/api/v1/hotels', $request->uri);
        self::assertSame(['page' => '2'], $request->query);
    }
}
