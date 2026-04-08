<?php

declare(strict_types=1);

function websms_database(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('WEBSMS_DB_HOST') ?: '127.0.0.1';
    $port = getenv('WEBSMS_DB_PORT') ?: '3306';
    $name = getenv('WEBSMS_DB_NAME') ?: 'websms';
    $user = getenv('WEBSMS_DB_USER') ?: 'root';
    $pass = getenv('WEBSMS_DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    return $pdo;
}