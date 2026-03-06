<?php
/**
 * config/db.php
 *
 * Central database configuration.
 * Returns a PDO instance using a persistent connection pool.
 *
 * HOW TO USE:
 *   require_once __DIR__ . '/../config/db.php';
 *   $db = get_db();
 */

// ── Credentials ────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'workshop_db');
define('DB_USER', 'root');
define('DB_PASS', '');          
define('DB_CHARSET', 'utf8mb4');

// ── Singleton PDO factory ───────────────────────────────────
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw exceptions on error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // return associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                    // use real prepared statements
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Never expose DB errors to the browser in production.
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('Database connection failed. Please contact the administrator.');
    }

    return $pdo;
}
