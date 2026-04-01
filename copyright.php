<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pageTitle = 'Copyright — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card readable">
    <h1>Copyright</h1>
    <p>Original text and structure created for NCC Education Back End Web Development assessment <strong>[2183-1]</strong>. Third-party embeds (e.g. video links) remain property of their owners.</p>
    <p>Recipe content in the seed database is illustrative for learning purposes.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
