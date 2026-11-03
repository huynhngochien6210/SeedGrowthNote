<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env
$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__)
);

$dotenv->safeLoad();

// Database configuration
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USERNAME'];
$dbPassword = $_ENV['DB_PASSWORD'];

// MySQL connection string
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

// PDO configuration
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

// Connect to Aiven MySQL using TLS
if (!empty($_ENV['DB_SSL_CA'])) {
    $caPath = $_ENV['DB_SSL_CA'];

    // Resolve relative certificate paths
    if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $caPath)) {
        $caPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $caPath;
    }

    if (!is_readable($caPath)) {
        throw new RuntimeException(
            "MySQL CA certificate not found or unreadable."
        );
    }

    $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $dbPassword, $options);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    throw new RuntimeException("Database connection failed.", 0, $e);
}
