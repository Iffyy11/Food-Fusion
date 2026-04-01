<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pageTitle = 'Cookies — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card readable">
    <h1>Cookie information</h1>
    <p><strong>Session cookie:</strong> When you log in, PHP issues a session cookie so pages can recognise you until you log out or the session expires.</p>
    <p><strong>Cookie banner choice:</strong> If you click “Accept”, we store your choice in <code>localStorage</code> in the browser so the banner stays dismissed.</p>
    <p>We do not use third-party advertising cookies in this demo build.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
