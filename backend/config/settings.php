<?php

declare(strict_types=1);

$env = static function (string $name, string $default = ''): string {
    $value = getenv($name);

    return $value === false ? $default : $value;
};

$root = dirname(__DIR__);

return [
    'app.debug' => $env('APP_DEBUG', '0') === '1',
    'log.level' => $env('LOG_LEVEL', 'info'),
    'path.storage' => $root . '/storage',
    'path.cache' => $root . '/var/cache',
    'path.seed_images' => $root . '/fixtures/images',
    'db' => [
        'driver' => 'pdo_pgsql',
        'host' => $env('DB_HOST', 'db'),
        'port' => (int) $env('DB_PORT', '5432'),
        'dbname' => $env('DB_NAME', 'products'),
        'user' => $env('DB_USER', 'app'),
        'password' => $env('DB_PASSWORD'),
        'charset' => 'utf8',
        'serverVersion' => '17',
    ],
    'messenger.dsn' => $env('MESSENGER_TRANSPORT_DSN'),
    'jwt.secret' => $env('JWT_SECRET'),
    'jwt.ttl' => (int) $env('JWT_TTL', '3600'),
    'admin.email' => $env('ADMIN_EMAIL', 'admin@example.com'),
    'admin.password' => $env('ADMIN_PASSWORD'),
    'import.max_file_size' => (int) $env('IMPORT_MAX_FILE_SIZE', '10485760'),
    'import.rate_limit' => (int) $env('IMPORT_RATE_LIMIT', '5'),
    'import.rate_interval' => $env('IMPORT_RATE_INTERVAL', '1 minute'),
    'image.timeout' => (int) $env('IMAGE_DOWNLOAD_TIMEOUT', '15'),
    'image.max_size' => (int) $env('IMAGE_MAX_SIZE', '10485760'),
    'image.concurrency' => (int) $env('IMAGE_DOWNLOAD_CONCURRENCY', '6'),
];
