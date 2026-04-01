<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/interactions.php';

$pdo = db();
$errors = [];
$sent = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $category = (string) ($_POST['category'] ?? 'general');
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }
    if (!in_array($category, ['general', 'recipe_request', 'feedback'], true)) {
        $category = 'general';
    }
    if ($subject === '' || strlen($subject) < 3) {
        $errors[] = 'Subject must be at least 3 characters.';
    }
    if ($message === '' || strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, category, subject, message) VALUES (:n,:e,:c,:s,:m)'
        );
        $stmt->execute([
            ':n' => $name,
            ':e' => $email,
            ':c' => $category,
            ':s' => $subject,
            ':m' => $message,
        ]);
        try {
            log_user_interaction($pdo, current_user_id(), 'contact_submit', 'contact_messages', (int) $pdo->lastInsertId(), null);
        } catch (Throwable) {
        }
        header('Location: contact.php?sent=1');
        exit;
    }
}

$pageTitle = 'Contact Us — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card readable">
    <h1>Contact Us</h1>
    <p class="lede">Enquiries, recipe requests, or feedback — we read every message.</p>

    <?php if ($sent): ?>
        <p class="notice success">Thank you. Your message has been saved and we will get back to you soon.</p>
    <?php else: ?>
        <?php if ($errors !== []): ?>
            <ul class="notice error">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" class="stack" novalidate>
            <label>Name <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Email <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Topic
                <select name="category">
                    <option value="general" <?= (($_POST['category'] ?? '') === 'general') ? 'selected' : '' ?>>General enquiry</option>
                    <option value="recipe_request" <?= (($_POST['category'] ?? '') === 'recipe_request') ? 'selected' : '' ?>>Recipe request</option>
                    <option value="feedback" <?= (($_POST['category'] ?? '') === 'feedback') ? 'selected' : '' ?>>Feedback</option>
                </select>
            </label>
            <label>Subject <input type="text" name="subject" required maxlength="200" value="<?= htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Message <textarea name="message" required rows="6" minlength="10" maxlength="4000"><?= htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></label>
            <button type="submit" class="btn primary">Send message</button>
        </form>
    <?php endif; ?>

    <h2>Other ways to reach us</h2>
    <p>Demo postal address: 12 Market Street, London · hello@foodfusion.example (fictitious)</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
