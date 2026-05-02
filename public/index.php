<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;

set_exception_handler(function (\Throwable $e): void {
    $response = JsonResponse::internalError($e->getMessage());
    $response->send();
});

$router = require __DIR__ . '/../bootstrap/routes.php';

$request  = Request::fromGlobals();
$response = $router->dispatch($request);
$response->send();