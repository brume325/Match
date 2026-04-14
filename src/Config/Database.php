<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

/**
 * Creates a PDO instance with strict defaults for transactional consistency.
 */
final class Database
{
    public static function connect(): PDO
    {
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME') ?: 'bd_matchmoove';
        $dbUser = getenv('DB_USER') ?: 'userdb';
        $dbPass = getenv('DB_PASS') ?: 'root';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

        try {
            return new PDO(
                $dsn,
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new PDOException('Database connection failed: ' . $exception->getMessage(), (int)$exception->getCode());
        }
    }
}
