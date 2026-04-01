<?php
declare(strict_types=1);

$__ffConfig = __DIR__ . '/config.php';
if (!is_file($__ffConfig)) {
    $__ffConfig = __DIR__ . '/config.example.php';
}
require_once $__ffConfig;
unset($__ffConfig);
