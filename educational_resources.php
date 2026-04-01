<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$rows = $pdo->query('SELECT * FROM educational_resources ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Educational Resources — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card readable resource-page-intro">
    <h1>Educational Resources</h1>
    <p class="lede">Food, energy, and everyday habits intersect in the kitchen. These readings, infographics, and clips explore renewables, waste, labels, and smarter use of power — alongside cooking.</p>
    <ul class="resource-highlights">
        <li><strong>PDFs</strong> — short guides you can download</li>
        <li><strong>Infographics</strong> — scannable summaries</li>
        <li><strong>Videos</strong> — placeholders; link your own for assessment</li>
    </ul>
</section>

<section class="card">
    <h2 class="sr-only">Learning library</h2>
    <ul class="resource-list resource-list-cards">
        <?php foreach ($rows as $r): ?>
            <li>
                <h3><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><span class="badge alt"><?= htmlspecialchars($r['resource_type'], ENT_QUOTES, 'UTF-8') ?></span></p>
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
