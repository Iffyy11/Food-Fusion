<?php
declare(strict_types=1);

require_once __DIR__ . '/config_load.php';
require_once __DIR__ . '/pdo_mysql_options.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, foodfusion_pdo_mysql_options());
        return $pdo;
    } catch (PDOException $e) {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            render_db_connection_help($e);
            exit;
        }
        throw $e;
    }
}

function render_db_connection_help(PDOException $e): void
{
    $detail = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $host = htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8');
    $port = (int) DB_PORT;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database not available — FoodFusion</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 42rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; color: #0f172a; }
        h1 { font-size: 1.35rem; }
        code { background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px; }
        .box { background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.9rem; margin: 1rem 0; word-break: break-word; }
        ol { padding-left: 1.25rem; }
        a { color: #0d9488; font-weight: 600; }
    </style>
</head>
<body>
    <h1>Cannot connect to MySQL</h1>
    <p>PHP could not open a connection to the database server. That usually means <strong>MySQL is not running</strong> or the host/port in <code>includes/config.php</code> is wrong.</p>
    <div class="box"><strong>Technical detail:</strong> <?= $detail ?></div>
    <h2>Fix on Windows (XAMPP)</h2>
    <ol>
        <li>Open <strong>XAMPP Control Panel</strong>.</li>
        <li>Click <strong>Start</strong> next to <strong>MySQL</strong> (wait until it shows “Running”).</li>
        <li>Confirm MySQL listens on port <strong><?= $port ?></strong> (default 3306). If you use another port, set <code>DB_PORT</code> in <code>includes/config.php</code>.</li>
        <li>Open <a href="setup.php">setup.php</a> once to create the <code><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></code> database and tables.</li>
    </ol>
    <h2>Other setups</h2>
    <ul>
        <li><strong>WAMP / Laragon:</strong> Start the MySQL service from the tray menu.</li>
        <li><strong>Docker:</strong> Run your MySQL container and set <code>DB_HOST</code> / <code>DB_PORT</code> to match (often <code>127.0.0.1</code> with a mapped port).</li>
    </ul>
    <p>Current settings: host <code><?= $host ?></code>, port <code><?= $port ?></code>, database <code><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></code>, user <code><?= htmlspecialchars(DB_USER, ENT_QUOTES, 'UTF-8') ?></code>.</p>
</body>
</html>
    <?php
}
