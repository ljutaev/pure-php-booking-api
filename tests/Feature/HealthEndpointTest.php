<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Presentation\Http\Request;
use App\Presentation\Http\Router;
use PHPUnit\Framework\TestCase;

class HealthEndpointTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = require __DIR__ . '/../../bootstrap/routes.php';
    }

    public function testHealthEndpointReturns200WithOkStatus(): void
    {
        $request = Request::create('GET', '/api/v1/health');
        $response = $this->router->dispatch($request);

        self::assertSame(200, $response->statusCode);
        self::assertSame('ok', $response->data['status']);
        self::assertArrayHasKey('timestamp', $response->data);
    }

    public function testReadinessEndpointReturns200(): void
    {
        $request = Request::create('GET', '/api/v1/health/ready');
        $response = $this->router->dispatch($request);

        self::assertSame(200, $response->statusCode);
        self::assertSame('ok', $response->data['status']);
    }
}
