<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env on localhost.
// On Render, environment variables are configured in the dashboard.
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Support environment variables provided by Render.
$envKeys = [
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_SSL_CA',
    'SMTP_HOST',
    'SMTP_PORT',
    'SMTP_USERNAME',
    'SMTP_PASSWORD',
    'SMTP_FROM_EMAIL',
    'SMTP_FROM_NAME',
    'APP_URL'
];

foreach ($envKeys as $key) {
    if (!isset($_ENV[$key])) {
        $value = getenv($key);

        if ($value !== false) {
            $_ENV[$key] = $value;
        }
    }
}

// Read required environment variables.
$requiredKeys = [
    'DB_HOST',
    'DB_NAME',
    'DB_USERNAME',
    'DB_PASSWORD'
];

foreach ($requiredKeys as $key) {
    if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
        throw new RuntimeException(
            "Missing required environment variable: {$key}"
        );
    }
}

// Database configuration.
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USERNAME'];
$dbPassword = $_ENV['DB_PASSWORD'];

// MySQL connection string.
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

// PDO configuration.
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

// Configure Aiven MySQL TLS CA if provided.
if (!empty($_ENV['DB_SSL_CA'])) {
    $caPath = $_ENV['DB_SSL_CA'];

    // Resolve relative certificate paths.
    if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $caPath)) {
        $caPath = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . $caPath;
    }

    if (!is_readable($caPath)) {
        throw new RuntimeException(
            'MySQL CA certificate not found or unreadable.'
        );
    }

    $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

// Establish database connection.
try {
    $pdo = new PDO(
        $dsn,
        $username,
        $dbPassword,
        $options
    );
} catch (PDOException $e) {
    error_log(
        'Database connection error: ' . $e->getMessage()
    );

    throw new RuntimeException(
        'Database connection failed.',
        0,
        $e
    );
}
