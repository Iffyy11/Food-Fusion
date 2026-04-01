<?php
declare(strict_types=1);

/** @return array<int, mixed> */
function foodfusion_pdo_mysql_options(): array
{
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (getenv('MYSQL_SSL_DISABLE_VERIFY') === '1') {
        $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    return $opts;
}
