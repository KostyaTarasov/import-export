<?php

declare(strict_types=1);

use App\Http\ErrorHandler;
use Slim\App;

return static function (App $app): void {
    $container = $app->getContainer();

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware((bool) $container?->get('app.debug'), false, false);
    $errorMiddleware->setDefaultErrorHandler(ErrorHandler::class);
};
