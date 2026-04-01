<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

function register_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

$error = '';
$isJson = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJson = strpos($ct, 'application/json') !== false;

    if ($isJson) {
        $data = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($data)) {
            register_json_response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }
        $_POST = array_merge($_POST, [
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => $data['password'] ?? '',
            'password_confirm' => $data['password_confirm'] ?? $data['password'] ?? '',
        ]);
    }

    $first = trim((string) ($_POST['first_name'] ?? ''));
    $last = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password_confirm'] ?? '');

    if ($first === '' || $last === '') {
        $error = 'Please enter your first and last name.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password === '' || strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = db();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
        $check->execute([':e' => $email]);
        if ($check->fetch()) {
            $error = 'That email is already registered. <a href="login.php">Log in</a> instead.';
            if ($isJson) {
                register_json_response(['ok' => false, 'message' => 'That email is already registered. Log in instead.'], 409);
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash) VALUES (:f, :l, :e, :p)'
            );
            $ins->execute([':f' => $first, ':l' => $last, ':e' => $email, ':p' => $hash]);
            if ($isJson) {
                register_json_response(['ok' => true, 'message' => 'Account created. You can log in now.']);
            }
            header('Location: login.php?registered=1');
            exit;
        }
    }

    if ($isJson && $error !== '') {
        register_json_response(['ok' => false, 'message' => html_entity_decode(strip_tags($error), ENT_QUOTES, 'UTF-8')], 400);
    }
}

$pageTitle = 'Register — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card narrow">
    <h1>Create your FoodFusion account</h1>
    <?php if ($error !== ''): ?>
        <p class="notice error"><?= $error ?></p>
    <?php endif; ?>
    <form method="post" action="register.php" class="stack" novalidate>
        <label>First name <input type="text" name="first_name" required autocomplete="given-name" value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Last name <input type="text" name="last_name" required autocomplete="family-name" value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Email <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Password <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label>Confirm password <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
        <button type="submit" class="btn primary">Register</button>
    </form>
    <p>Already registered? <a href="login.php">Log in</a></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
