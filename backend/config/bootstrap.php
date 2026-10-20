<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return static function (array $definitions = []): ContainerInterface {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(__DIR__ . '/settings.php', __DIR__ . '/container.php', $definitions);

    return $builder->build();
};
