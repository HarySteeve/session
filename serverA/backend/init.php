<?php

function getConnection(): \PDO
{
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASSWORD') ?: 'root';
    $dbName = getenv('DB_NAME') ?: 'session_db';

    $dsn = "mysql:host=haproxy;dbname={$dbName};charset=utf8";
    try {
        return new \PDO($dsn, $dbUser, $dbPass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    } catch (\PDOException $e) {
        throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }
}

require_once __DIR__ . '/MySessionHandler.php';

try {
    $pdo = getConnection();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Database connection error: ' . $e->getMessage();
    exit;
}

$handler = new MySessionHandler($pdo);
session_set_save_handler($handler, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
