<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Presentation\Http\Request;

$router = require __DIR__ . '/../bootstrap/routes.php';

$request = Request::fromGlobals();
$response = $router->dispatch($request);
$response->send();
