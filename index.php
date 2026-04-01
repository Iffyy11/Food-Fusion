<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle = 'FoodFusion — Home';

$events = [];
$newsItems = [];
$stats = ['u' => 0, 'r' => 0, 'c' => 0];
$featuredRecipes = [];
$recentCommunity = [];
$dbOk = true;

try {
    require_once __DIR__ . '/includes/db.php';
    $pdo = db();
    $events = $pdo->query('SELECT id, title, description, event_datetime FROM events ORDER BY event_datetime ASC LIMIT 6')->fetchAll();
    $newsItems = $pdo->query('SELECT title, summary, link, published_at FROM news_items ORDER BY published_at DESC')->fetchAll();
    $row = $pdo->query(
        'SELECT (SELECT COUNT(*) FROM users) AS u, (SELECT COUNT(*) FROM recipes) AS r, (SELECT COUNT(*) FROM community_recipes) AS c'
    )->fetch();
    if ($row) {
        $stats = ['u' => (int) $row['u'], 'r' => (int) $row['r'], 'c' => (int) $row['c']];
    }
    $featuredRecipes = $pdo->query(
        'SELECT id, title, description, cuisine_type, difficulty, prep_minutes FROM recipes WHERE is_featured = 1 ORDER BY title LIMIT 4'
    )->fetchAll();
    $recentCommunity = $pdo->query(
        'SELECT c.id, c.title, c.created_at, u.first_name, u.last_name FROM community_recipes c JOIN users u ON u.id = c.user_id ORDER BY c.created_at DESC LIMIT 4'
    )->fetchAll();
} catch (Throwable) {
    $dbOk = false;
    $newsItems = [
        ['title' => 'Run database setup', 'summary' => 'Open setup.php after configuring includes/config.php and MySQL.', 'link' => 'setup.php', 'published_at' => date('Y-m-d H:i:s')],
    ];
}

require __DIR__ . '/includes/header.php';
?>

<div class="home-page">
<section class="hero hero-home hero-home-modern" aria-label="Upcoming cooking events">
    <div class="events-carousel" id="eventsCarousel">
        <?php if ($dbOk && count($events) > 0): ?>
            <?php foreach ($events as $i => $ev): ?>
                <div class="event-slide<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
                    <div class="event-slide-inner">
                        <p class="event-kicker">Upcoming event</p>
                        <h1><?= htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p><?= htmlspecialchars($ev['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <time class="event-time" datetime="<?= htmlspecialchars($ev['event_datetime'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($ev['event_datetime'], ENT_QUOTES, 'UTF-8') ?>
                        </time>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="slider-dots" role="tablist" aria-label="Events">
                <?php foreach ($events as $i => $ev): ?>
                    <button type="button" class="dot<?= $i === 0 ? ' active' : '' ?>" data-slide="<?= $i ?>" aria-label="Event <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="event-slide active">
                <div class="event-slide-inner">
                    <p class="event-kicker">FoodFusion</p>
                    <h1>Cook together, learn together</h1>
                    <p>Add events via the database after running <a href="setup.php">setup</a>.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($dbOk): ?>
<section class="stats-strip stats-strip-modern" aria-label="Community snapshot">
    <div class="stat-card">
        <span class="stat-value" data-count="<?= $stats['u'] ?>"><?= $stats['u'] ?></span>
        <span class="stat-label">Cooks joined</span>
    </div>
    <div class="stat-card">
        <span class="stat-value" data-count="<?= $stats['r'] ?>"><?= $stats['r'] ?></span>
        <span class="stat-label">Curated recipes</span>
    </div>
    <div class="stat-card">
        <span class="stat-value" data-count="<?= $stats['c'] ?>"><?= $stats['c'] ?></span>
        <span class="stat-label">Community posts</span>
    </div>
</section>
<?php endif; ?>

<section class="home-bento" aria-label="Quick navigation">
    <a class="home-bento-tile" href="recipes.php">
        <span class="home-bento-kicker">Collection</span>
        <strong>Recipe hub</strong>
        <span class="home-bento-desc">Search, filters &amp; saved dishes</span>
    </a>
    <a class="home-bento-tile" href="recipes.php#mealdb-heading">
        <span class="home-bento-kicker">Discover</span>
        <strong>Global ideas</strong>
        <span class="home-bento-desc">TheMealDB browse on-site</span>
    </a>
    <a class="home-bento-tile" href="community.php">
        <span class="home-bento-kicker">Together</span>
        <strong>Community</strong>
        <span class="home-bento-desc">Cookbook &amp; comments</span>
    </a>
    <a class="home-bento-tile" href="culinary_resources.php">
        <span class="home-bento-kicker">Learn</span>
        <strong>Resources</strong>
        <span class="home-bento-desc">Cards, tutorials &amp; clips</span>
    </a>
</section>

<section class="card mission-card mission-card-modern">
    <div class="mission-grid">
        <div>
            <p class="eyebrow">Why FoodFusion</p>
            <h2>Recipes, skills &amp; community</h2>
            <p class="lede">Your hub for curated dishes, practical techniques, and a cookbook built by members. Save favourites, search the collection, and share what you cook.</p>
            <div class="cta-row">
                <button type="button" class="btn primary" id="openJoinUs">Join Us</button>
                <button type="button" class="btn secondary" id="openSignUpNow">Sign up Now</button>
                <a class="btn ghost" href="recipes.php">Browse recipes</a>
            </div>
        </div>
        <div class="mission-aside">
            <h3>Quick links</h3>
            <ul class="quick-links">
                <li><a href="recipes.php?q=vegan">Vegan ideas</a></li>
                <li><a href="recipes.php?difficulty=easy">Easy dinners</a></li>
                <li><a href="community.php">Latest community posts</a></li>
                <li><a href="culinary_resources.php">Downloads &amp; tutorials</a></li>
            </ul>
        </div>
    </div>
    <p class="small subtle">Join Us creates a full account. Sign up Now is for email updates.</p>
</section>

<?php if ($dbOk && count($featuredRecipes) > 0): ?>
<section class="featured-section featured-section-modern">
    <div class="section-head">
        <h2>Featured picks</h2>
        <a href="recipes.php" class="link-arrow">View all</a>
    </div>
    <ul class="featured-grid">
        <?php foreach ($featuredRecipes as $fr): ?>
            <li>
                <a class="featured-tile" href="recipes.php#recipe-<?= (int) $fr['id'] ?>">
                    <span class="featured-tile-meta"><?= htmlspecialchars($fr['cuisine_type'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($fr['difficulty'], ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($fr['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php
                    $fd = (string) $fr['description'];
                    $fdShort = strlen($fd) > 88 ? substr($fd, 0, 85) . '…' : $fd;
                    ?>
                    <span class="featured-tile-desc"><?= htmlspecialchars($fdShort, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($fr['prep_minutes']): ?>
                        <span class="featured-time"><?= (int) $fr['prep_minutes'] ?> min</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="card two-col home-split home-split-modern">
    <div>
        <h2>News &amp; trends</h2>
        <p class="sr-only">Featured recipes and culinary trends</p>
        <ul class="news-feed" role="feed">
            <?php foreach ($newsItems as $i => $n): ?>
                <li class="news-item">
                    <article>
                        <h3><?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <time datetime="<?= htmlspecialchars($n['published_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($n['published_at'], ENT_QUOTES, 'UTF-8') ?></time>
                        <p><?= htmlspecialchars($n['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($n['link'])): ?>
                            <a href="<?= htmlspecialchars($n['link'], ENT_QUOTES, 'UTF-8') ?>">Read more</a>
                        <?php endif; ?>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div>
        <h2>From the community</h2>
        <?php if ($dbOk && count($recentCommunity) > 0): ?>
            <ul class="community-mini">
                <?php foreach ($recentCommunity as $p): ?>
                    <li>
                        <a href="community.php?id=<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?></a>
                        <span class="meta"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($p['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="link-arrow" href="community.php">Open cookbook</a>
        <?php else: ?>
            <p class="muted">No posts yet — <a href="community.php">share the first recipe</a> in the Community Cookbook.</p>
        <?php endif; ?>
        <h3 class="social-title">Social</h3>
        <p class="small">Follow for reels, live cooks, and challenges.</p>
        <?php
        $social_ul_class = 'social social-icons inline-social';
        require __DIR__ . '/includes/social_icons.php';
        ?>
        <p class="small"><a href="privacy.php">Privacy</a> · <a href="cookies.php">Cookies</a></p>
    </div>
</section>

</div>

<div id="cookieBanner" class="cookie-banner" role="dialog" aria-labelledby="cookieTitle" aria-hidden="true" hidden>
    <div class="cookie-inner">
        <h2 id="cookieTitle">Cookies</h2>
        <p>We use essential cookies for your session when you log in. See our <a href="cookies.php">cookie information</a> and <a href="privacy.php">privacy policy</a>.</p>
        <button type="button" class="btn primary" id="acceptCookies">Accept</button>
    </div>
</div>

<div id="joinUsModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="joinTitle" hidden>
    <div class="modal-backdrop" data-close-join></div>
    <div class="modal-panel">
        <button type="button" class="modal-close" aria-label="Close" data-close-join>&times;</button>
        <p class="eyebrow">Account</p>
        <h2 id="joinTitle">Join Us</h2>
        <p>Create your FoodFusion account.</p>
        <form id="joinUsForm" class="stack">
            <label>First name <input type="text" name="first_name" required autocomplete="given-name"></label>
            <label>Last name <input type="text" name="last_name" required autocomplete="family-name"></label>
            <label>Email <input type="email" name="email" required autocomplete="email"></label>
            <label>Password <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <button type="submit" class="btn primary">Create account</button>
        </form>
        <p id="joinUsMsg" class="notice" hidden></p>
    </div>
</div>

<div id="signUpNowModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="signUpTitle" hidden>
    <div class="modal-backdrop" data-close-su></div>
    <div class="modal-panel">
        <button type="button" class="modal-close" aria-label="Close" data-close-su>&times;</button>
        <p class="eyebrow">Newsletter</p>
        <h2 id="signUpTitle">Sign up Now</h2>
        <p>Get seasonal recipes and event invites by email.</p>
        <form id="signUpNowForm" class="stack">
            <label>Email <input type="email" name="email" required autocomplete="email"></label>
            <label>First name (optional) <input type="text" name="first_name" autocomplete="given-name"></label>
            <label>Last name (optional) <input type="text" name="last_name" autocomplete="family-name"></label>
            <button type="submit" class="btn primary">Sign up</button>
        </form>
        <p id="signUpNowMsg" class="notice" hidden></p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
