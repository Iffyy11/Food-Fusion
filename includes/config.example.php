<?php
declare(strict_types=1);

// Copy this file to config.php and set your database credentials.
// Use 127.0.0.1 on Windows so PHP uses TCP (not a missing MySQL socket).
const DB_HOST = '127.0.0.1';
/** MySQL port — XAMPP/WAMP default is 3306 */
const DB_PORT = 3306;
const DB_NAME = 'foodfusion';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

const LOGIN_ATTEMPT_LIMIT = 3;
const LOGIN_ATTEMPT_RESET_SECONDS = 180;
