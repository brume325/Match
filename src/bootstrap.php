<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Env;

spl_autoload_register(static function (string $className): void {
    $prefix = 'App\\';
    if (!str_starts_with($className, $prefix)) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});

Env::load(dirname(__DIR__));

return Database::connect();
