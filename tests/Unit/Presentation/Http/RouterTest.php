<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http;

use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;
use App\Presentation\Http\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testDispatchesRegisteredGetRoute(): void
    {
        $router = new Router();
        $router->get('/api/v1/test', fn () => JsonResponse::ok(['ok' => true]));

        $response = $router->dispatch(Request::create('GET', '/api/v1/test'));

        self::assertSame(200, $response->statusCode);
    }

    public function testReturns404ForUnknownRoute(): void
    {
        $router = new Router();

        $response = $router->dispatch(Request::create('GET', '/not-found'));

        self::assertSame(404, $response->statusCode);
    }

    public function testReturns405ForWrongMethod(): void
    {
        $router = new Router();
        $router->get('/api/v1/test', fn () => JsonResponse::ok([]));

        $response = $router->dispatch(Request::create('POST', '/api/v1/test'));

        self::assertSame(405, $response->statusCode);
    }

    public function testPassesRequestToHandler(): void
    {
        $router = new Router();
        $captured = null;
        $router->get('/api/v1/test', function (Request $req) use (&$captured): JsonResponse {
            $captured = $req;
            return JsonResponse::ok([]);
        });

        $router->dispatch(Request::create('GET', '/api/v1/test'));

        self::assertInstanceOf(Request::class, $captured);
    }
}
