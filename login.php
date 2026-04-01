<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$info = '';

login_attempts_reset_if_expired();

$pdo = db();
$pdo->exec('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE locked_until IS NOT NULL AND locked_until <= NOW()');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } elseif (login_failure_count() >= LOGIN_ATTEMPT_LIMIT) {
        $error = 'Too many failed attempts from this browser. Wait ' . LOGIN_ATTEMPT_RESET_SECONDS . ' seconds, then try again or <a href="register.php">create an account</a>.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, first_name, last_name, email, password_hash, failed_login_attempts, locked_until FROM users WHERE email = :e LIMIT 1'
        );
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch();

        if ($row) {
            $lockedUntil = $row['locked_until'] ? strtotime((string) $row['locked_until']) : false;
            if ($lockedUntil && $lockedUntil > time()) {
                $mins = max(1, (int) ceil(($lockedUntil - time()) / 60));
                $error = 'This account is temporarily locked after ' . LOGIN_ATTEMPT_LIMIT . ' failed attempts. Try again in about ' . $mins . ' minute(s).';
            } elseif (password_verify($password, $row['password_hash'])) {
                $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id')->execute([':id' => $row['id']]);
                login_reset_success();
                $_SESSION['user_id'] = (int) $row['id'];
                $_SESSION['user_email'] = (string) $row['email'];
                $_SESSION['user_first_name'] = (string) $row['first_name'];
                $_SESSION['user_last_name'] = (string) $row['last_name'];
                header('Location: index.php');
                exit;
            } else {
                $fail = (int) $row['failed_login_attempts'] + 1;
                if ($fail >= LOGIN_ATTEMPT_LIMIT) {
                    $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_ATTEMPT_RESET_SECONDS);
                    $pdo->prepare(
                        'UPDATE users SET failed_login_attempts = :f, locked_until = :u WHERE id = :id'
                    )->execute([':f' => $fail, ':u' => $lockUntil, ':id' => $row['id']]);
                    $error = 'Too many failed attempts. This account is locked for ' . (LOGIN_ATTEMPT_RESET_SECONDS / 60) . ' minutes.';
                } else {
                    $pdo->prepare('UPDATE users SET failed_login_attempts = :f WHERE id = :id')->execute([':f' => $fail, ':id' => $row['id']]);
                    $error = 'Invalid email or password.';
                }
            }
        } else {
            login_record_failure();
            $error = 'Invalid email or password.';
            if (login_should_prompt_register()) {
                $error .= ' You have failed ' . LOGIN_ATTEMPT_LIMIT . ' times from this browser — wait ' . (LOGIN_ATTEMPT_RESET_SECONDS / 60) . ' minutes or <a href="register.php">register</a>.';
            }
        }
    }
}

if (isset($_GET['registered'])) {
    $info = 'Registration successful. You can log in now.';
}

$pageTitle = 'Login — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card narrow">
    <h1>Log in</h1>
    <?php if ($info !== ''): ?>
        <p class="notice success"><?= $info ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="notice error"><?= $error ?></p>
    <?php endif; ?>
    <?php if (login_failure_count() > 0 && login_failure_count() < LOGIN_ATTEMPT_LIMIT): ?>
        <p class="notice warn">Failed attempts (this browser): <?= login_failure_count() ?> / <?= LOGIN_ATTEMPT_LIMIT ?>.</p>
    <?php endif; ?>
    <form method="post" action="login.php" class="stack" novalidate>
        <label>Email <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit" class="btn primary">Log in</button>
    </form>
    <p>New here? <a href="register.php">Create an account</a> or use <strong>Join Us</strong> on the home page.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
