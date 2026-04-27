<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http;

use App\Presentation\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    public function testOkCreates200Response(): void
    {
        $response = JsonResponse::ok(['status' => 'ok']);

        self::assertSame(200, $response->statusCode);
        self::assertSame(['status' => 'ok'], $response->data);
    }

    public function testCreatedCreates201Response(): void
    {
        $response = JsonResponse::created(['id' => 'uuid-123']);

        self::assertSame(201, $response->statusCode);
    }

    public function testNotFoundCreates404Response(): void
    {
        $response = JsonResponse::notFound('Resource not found');

        self::assertSame(404, $response->statusCode);
        self::assertSame('NOT_FOUND', $response->data['error']['code']);
        self::assertSame('Resource not found', $response->data['error']['message']);
    }

    public function testMethodNotAllowedCreates405Response(): void
    {
        $response = JsonResponse::methodNotAllowed();

        self::assertSame(405, $response->statusCode);
    }
}
