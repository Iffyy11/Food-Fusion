<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$uid = current_user_id();
$flash = '';
$flashOk = true;

$stmt = $pdo->prepare('SELECT id, first_name, last_name, email, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim((string) ($_POST['first_name'] ?? ''));
    $last = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($first === '' || $last === '') {
        $flash = 'First and last name are required.';
        $flashOk = false;
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash = 'Please enter a valid email address.';
        $flashOk = false;
    } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
        $flash = 'New password must be at least 8 characters.';
        $flashOk = false;
    } elseif ($newPassword !== $confirmPassword) {
        $flash = 'New password and confirmation do not match.';
        $flashOk = false;
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $check->execute([':email' => $email, ':id' => $uid]);
        if ($check->fetch()) {
            $flash = 'That email is already in use by another account.';
            $flashOk = false;
        } else {
            if ($newPassword !== '') {
                $upd = $pdo->prepare(
                    'UPDATE users SET first_name = :first, last_name = :last, email = :email, password_hash = :hash WHERE id = :id'
                );
                $upd->execute([
                    ':first' => $first,
                    ':last' => $last,
                    ':email' => $email,
                    ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    ':id' => $uid,
                ]);
            } else {
                $upd = $pdo->prepare(
                    'UPDATE users SET first_name = :first, last_name = :last, email = :email WHERE id = :id'
                );
                $upd->execute([
                    ':first' => $first,
                    ':last' => $last,
                    ':email' => $email,
                    ':id' => $uid,
                ]);
            }

            $_SESSION['user_first_name'] = $first;
            $_SESSION['user_last_name'] = $last;
            $_SESSION['user_email'] = $email;

            $flash = 'Profile updated successfully.';
            $flashOk = true;

            $stmt->execute([':id' => $uid]);
            $user = $stmt->fetch();
        }
    }
}

$stats = [
    'favorites' => 0,
    'community_posts' => 0,
    'comments' => 0,
];

$statsStmt = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM user_favorites WHERE user_id = :uid1) AS favorites,
        (SELECT COUNT(*) FROM community_recipes WHERE user_id = :uid2) AS community_posts,
        (SELECT COUNT(*) FROM community_comments WHERE user_id = :uid3) AS comments'
);
$statsStmt->execute([
    ':uid1' => $uid,
    ':uid2' => $uid,
    ':uid3' => $uid,
]);
$row = $statsStmt->fetch();
if ($row) {
    $stats = [
        'favorites' => (int) $row['favorites'],
        'community_posts' => (int) $row['community_posts'],
        'comments' => (int) $row['comments'],
    ];
}

$pageTitle = 'My Profile — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card profile-page">
    <h1>My Profile</h1>
    <p class="lede">Manage your account details and keep your community profile up to date.</p>
    <?php if ($flash !== ''): ?>
        <p class="notice <?= $flashOk ? 'success' : 'error' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="profile-stats">
        <article class="profile-stat-card">
            <span class="profile-stat-number"><?= $stats['favorites'] ?></span>
            <span class="profile-stat-label">Saved favourites</span>
        </article>
        <article class="profile-stat-card">
            <span class="profile-stat-number"><?= $stats['community_posts'] ?></span>
            <span class="profile-stat-label">Community posts</span>
        </article>
        <article class="profile-stat-card">
            <span class="profile-stat-number"><?= $stats['comments'] ?></span>
            <span class="profile-stat-label">Comments left</span>
        </article>
    </div>
</section>

<section class="card profile-form-card">
    <h2>Account details</h2>
    <p class="small subtle">Joined <?= htmlspecialchars((string) $user['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" class="stack" novalidate>
        <label>First name
            <input type="text" name="first_name" required maxlength="80" autocomplete="given-name" value="<?= htmlspecialchars((string) $user['first_name'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>Last name
            <input type="text" name="last_name" required maxlength="80" autocomplete="family-name" value="<?= htmlspecialchars((string) $user['last_name'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>Email
            <input type="email" name="email" required maxlength="255" autocomplete="email" value="<?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>New password (optional)
            <input type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password">
        </label>
        <label>Confirm new password
            <input type="password" name="confirm_password" minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn primary">Save changes</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
