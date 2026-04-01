<?php
declare(strict_types=1);

require_once __DIR__ . '/config_load.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_login(): void
{
    if (current_user_id() === null) {
        header('Location: login.php');
        exit;
    }
}

function login_attempt_init(): void
{
    if (!isset($_SESSION['login_failures'])) {
        $_SESSION['login_failures'] = 0;
    }
    if (!isset($_SESSION['login_first_fail_at'])) {
        $_SESSION['login_first_fail_at'] = null;
    }
}

function login_attempts_reset_if_expired(): void
{
    login_attempt_init();
    $t = $_SESSION['login_first_fail_at'];
    if ($t !== null && (time() - (int) $t) >= LOGIN_ATTEMPT_RESET_SECONDS) {
        $_SESSION['login_failures'] = 0;
        $_SESSION['login_first_fail_at'] = null;
    }
}

function login_record_failure(): void
{
    login_attempt_init();
    if ($_SESSION['login_failures'] === 0) {
        $_SESSION['login_first_fail_at'] = time();
    }
    $_SESSION['login_failures'] = (int) $_SESSION['login_failures'] + 1;
}

function login_reset_success(): void
{
    $_SESSION['login_failures'] = 0;
    $_SESSION['login_first_fail_at'] = null;
}

function login_failure_count(): int
{
    login_attempts_reset_if_expired();
    login_attempt_init();
    return (int) $_SESSION['login_failures'];
}

function login_should_prompt_register(): bool
{
    return login_failure_count() >= LOGIN_ATTEMPT_LIMIT;
}
