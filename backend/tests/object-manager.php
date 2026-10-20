<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

return (require dirname(__DIR__) . '/config/bootstrap.php')()->get(EntityManagerInterface::class);
