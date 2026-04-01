<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pageTitle = 'Privacy — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card readable">
    <h1>Privacy policy</h1>
    <p>FoodFusion (this student project) explains how personal data is handled in line with assessment requirements.</p>
    <ul>
        <li><strong>Accounts:</strong> We store your name, email, and a bcrypt password hash in MySQL.</li>
        <li><strong>Community &amp; contact:</strong> Messages and cookbook posts are stored to provide the service.</li>
        <li><strong>Newsletter:</strong> “Sign up Now” stores email (and optional names) in <code>newsletter_signups</code>.</li>
        <li><strong>Interactions:</strong> We may log anonymous or signed-in actions in <code>user_interactions</code> for coursework demonstration.</li>
        <li><strong>Your rights:</strong> In a live site you would request export/deletion via a documented process.</li>
    </ul>
    <p>See also <a href="cookies.php">Cookie information</a>.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
