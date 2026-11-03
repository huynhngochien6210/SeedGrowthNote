<?php
declare(strict_types=1);

/**
 * SeedGrowthNote Database Configuration
 *
 * MySQL Aiven + PDO
 * Supports local .env and Render Environment Variables.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env for local development.
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

/**
 * Read environment variables from Render or .env.
 */
function dbEnv(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return $value;
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

    if ($value !== null && $value !== '') {
        return (string) $value;
    }

    return $default;
}

/**
 * Database configuration.
 */
$dbHost = dbEnv('DB_HOST', 'localhost');
$dbPort = dbEnv('DB_PORT', '3306');
$dbName = dbEnv('DB_NAME');
$dbUser = dbEnv('DB_USER');
$dbPass = dbEnv('DB_PASS', '');

if (
    $dbName === null ||
    $dbUser === null
) {
    throw new RuntimeException(
        'Missing database environment variables.'
    );
}

/**
 * Create PDO connection.
 */
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbHost,
        $dbPort,
        $dbName
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10
    ];

    /**
     * Aiven MySQL uses TLS.
     *
     * Set DB_SSL_CA to the absolute path of the
     * Aiven CA certificate for verified TLS.
     */
    $sslCa = dbEnv('DB_SSL_CA');

    if ($sslCa !== null) {
        if (!is_readable($sslCa)) {
            throw new RuntimeException(
                'Database SSL CA file is not readable.'
            );
        }

        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        $options
    );

} catch (PDOException $e) {
    error_log(
        'Database connection failed: ' .
        $e->getMessage()
    );

    throw new RuntimeException(
        'Unable to connect to the database.',
        0,
        $e
    );
}
