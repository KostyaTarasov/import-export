<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\ProductController;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->get('/api/health', [HealthController::class, 'check']);
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    $app->group('/api', function (RouteCollectorProxy $api): void {
        $api->get('/auth/me', [AuthController::class, 'me']);

        $api->get('/products', [ProductController::class, 'list']);
        $api->get('/products/{id:[1-9][0-9]{0,8}}', [ProductController::class, 'show']);
    })->add(JwtAuthMiddleware::class);
};
