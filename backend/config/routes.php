<?php

declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\ProductController;
use Slim\App;

return static function (App $app): void {
    $app->get('/api/health', [HealthController::class, 'check']);

    $app->get('/api/products', [ProductController::class, 'list']);
    $app->get('/api/products/{id:[1-9][0-9]{0,8}}', [ProductController::class, 'show']);
};
