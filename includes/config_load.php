<?php
declare(strict_types=1);

/**
 * Load DB constants: env / DATABASE_URL first (Render, Docker, etc.), else config.php / config.example.php.
 */
if (defined('DB_HOST')) {
    return;
}

/**
 * @param array<string, mixed> $parts
 */
function foodfusion_define_db_from_parts(array $parts): void
{
    define('DB_HOST', (string) ($parts['host'] ?? '127.0.0.1'));
    define('DB_PORT', isset($parts['port']) ? (int) $parts['port'] : 3306);
    $path = (string) ($parts['path'] ?? '/foodfusion');
    define('DB_NAME', ltrim($path, '/') !== '' ? ltrim($path, '/') : 'foodfusion');
    define('DB_USER', (string) ($parts['user'] ?? 'root'));
    define('DB_PASS', isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : '');
    define('DB_CHARSET', 'utf8mb4');
    define('LOGIN_ATTEMPT_LIMIT', 3);
    define('LOGIN_ATTEMPT_RESET_SECONDS', 180);
}

$url = getenv('DATABASE_URL');
if (is_string($url) && $url !== '') {
    // Render Postgres uses postgres:// — this app needs MySQL only.
    if (str_starts_with($url, 'postgres://') || str_starts_with($url, 'postgresql://')) {
        throw new RuntimeException(
            'FoodFusion requires MySQL. Render’s managed database is PostgreSQL. ' .
            'Use an external MySQL-compatible service and set DATABASE_URL to a mysql:// connection string, ' .
            'or set DB_HOST, DB_NAME, DB_USER, DB_PASS (and DB_PORT) instead.'
        );
    }
    if (str_starts_with($url, 'mysql://') || str_starts_with($url, 'mysql2://')) {
        $parsed = parse_url($url);
        if (!is_array($parsed) || empty($parsed['host'])) {
            throw new RuntimeException('Invalid DATABASE_URL for MySQL.');
        }
        foodfusion_define_db_from_parts($parsed);
        define('FOODFUSION_MANAGED_DB', true);
        return;
    }
}

if (getenv('DB_HOST') !== false && getenv('DB_HOST') !== '') {
    foodfusion_define_db_from_parts([
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT') !== false && getenv('DB_PORT') !== '' ? (int) getenv('DB_PORT') : 3306,
        'path' => '/' . (getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? getenv('DB_NAME') : 'foodfusion'),
        'user' => getenv('DB_USER') !== false ? getenv('DB_USER') : 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    ]);
    if (getenv('DB_SKIP_CREATE_DATABASE') === '1') {
        define('FOODFUSION_MANAGED_DB', true);
    }
    return;
}

$__ffConfig = __DIR__ . '/config.php';
if (!is_file($__ffConfig)) {
    $__ffConfig = __DIR__ . '/config.example.php';
}
require_once $__ffConfig;
unset($__ffConfig);
