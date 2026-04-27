<?php

declare(strict_types=1);

use App\Presentation\Controller\HealthController;
use App\Presentation\Http\Router;

$router = new Router();
$health = new HealthController();

$router->get('/api/v1/health', [$health, 'health']);
$router->get('/api/v1/health/ready', [$health, 'ready']);

return $router;
