<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;

require dirname(__DIR__) . '/vendor/autoload.php';

$settings = require dirname(__DIR__) . '/config/settings.php';
$database = $settings['db']['dbname'];

$server = DriverManager::getConnection(['dbname' => 'postgres'] + $settings['db']);

if ($server->fetchOne('SELECT 1 FROM pg_database WHERE datname = ?', [$database]) === false) {
    $server->executeStatement('CREATE DATABASE ' . $server->quoteSingleIdentifier($database));
}

$server->close();

passthru(sprintf('%s %s migrations:migrate --no-interaction --quiet', PHP_BINARY, escapeshellarg(dirname(__DIR__) . '/bin/console')), $exitCode);

if ($exitCode !== 0) {
    exit($exitCode);
}
