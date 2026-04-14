<?php
// Connexion PDO centralisee dans le dossier database.

declare(strict_types=1);

function db_connect_from_env(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'bd_matchmoove';
    $user = getenv('DB_USER') ?: 'userdb';
    $pass = getenv('DB_PASS') ?: 'root';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
