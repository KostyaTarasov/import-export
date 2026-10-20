<?php

declare(strict_types=1);

use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

$container = (require __DIR__ . '/../config/bootstrap.php')();
$container->get(App::class)->run();
