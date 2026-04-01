<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$rows = $pdo->query('SELECT * FROM culinary_resources ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Culinary Resources — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card readable resource-page-intro">
    <h1>Culinary Resources</h1>
    <p class="lede">Printable cards, technique tutorials, and video placeholders — from roux to mise en place. Swap in your own files when you submit coursework.</p>
    <ul class="resource-highlights">
        <li><strong>Cards</strong> — quick-reference sheets</li>
        <li><strong>Tutorials</strong> — step-by-step notes</li>
        <li><strong>Videos</strong> — external links (replace with your embeds)</li>
    </ul>
</section>

<section class="card">
    <h2 class="sr-only">Resource list</h2>
    <ul class="resource-list resource-list-cards">
        <?php foreach ($rows as $r): ?>
            <li>
                <h3><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><span class="badge"><?= htmlspecialchars($r['resource_type'], ENT_QUOTES, 'UTF-8') ?></span></p>
                <?php
                $url = (string) $r['file_url'];
                $isExt = preg_match('#^https?://#i', $url) === 1;
                ?>
                <?php if ($isExt): ?>
                    <a class="btn primary" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Open resource</a>
                <?php else: ?>
                    <a class="btn primary" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" download>Download</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
