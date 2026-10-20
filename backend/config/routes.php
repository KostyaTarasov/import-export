<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\ImportController;
use App\Controller\ProductController;
use App\Middleware\ImportRateLimitMiddleware;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    $app->get('/api/health', [HealthController::class, 'check']);
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    $app->group('/api', function (RouteCollectorProxy $api) use ($uuid): void {
        $api->get('/auth/me', [AuthController::class, 'me']);

        $api->get('/products', [ProductController::class, 'list']);
        $api->get('/products/{id:[1-9][0-9]{0,8}}', [ProductController::class, 'show']);

        $api->post('/imports', [ImportController::class, 'upload'])->add(ImportRateLimitMiddleware::class);
        $api->get('/imports/{id:' . $uuid . '}', [ImportController::class, 'show']);
        $api->get('/imports/{id:' . $uuid . '}/errors', [ImportController::class, 'errors']);
    })->add(JwtAuthMiddleware::class);
};
