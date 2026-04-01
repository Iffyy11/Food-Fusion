<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/interactions.php';
require_once __DIR__ . '/includes/themealdb.php';

$pdo = db();
$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
    require_login();
    $rid = (int) ($_POST['recipe_id'] ?? 0);
    $returnRaw = (string) ($_POST['return_url'] ?? 'recipes.php');
    $return = 'recipes.php';
    $path = parse_url($returnRaw, PHP_URL_PATH);
    $query = parse_url($returnRaw, PHP_URL_QUERY);
    if ($path && (basename((string) $path) === 'recipes.php') && strpos($returnRaw, '..') === false) {
        $return = 'recipes.php' . ($query !== null && $query !== '' ? '?' . $query : '');
    }
    if ($rid > 0) {
        $chk = $pdo->prepare('SELECT 1 FROM user_favorites WHERE user_id = :u AND recipe_id = :r');
        $chk->execute([':u' => current_user_id(), ':r' => $rid]);
        if ($chk->fetch()) {
            $pdo->prepare('DELETE FROM user_favorites WHERE user_id = :u AND recipe_id = :r')->execute([
                ':u' => current_user_id(),
                ':r' => $rid,
            ]);
        } else {
            $pdo->prepare('INSERT INTO user_favorites (user_id, recipe_id) VALUES (:u, :r)')->execute([
                ':u' => current_user_id(),
                ':r' => $rid,
            ]);
        }
        try {
            log_user_interaction($pdo, current_user_id(), 'favorite_toggle', 'recipe', $rid, null);
        } catch (Throwable) {
        }
    }
    header('Location: ' . $return);
    exit;
}

$cuisine = trim((string) ($_GET['cuisine'] ?? ''));
$dietary = trim((string) ($_GET['dietary'] ?? ''));
$difficulty = trim((string) ($_GET['difficulty'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$savedOnly = isset($_GET['saved']) && $_GET['saved'] === '1' && $uid !== null;

$sql = 'SELECT r.id, r.title, r.slug, r.description, r.instructions, r.cuisine_type, r.dietary_preference, r.difficulty, r.prep_minutes, r.is_featured FROM recipes r';
$params = [];
if ($savedOnly) {
    $sql .= ' INNER JOIN user_favorites f ON f.recipe_id = r.id AND f.user_id = :fav_uid';
    $params[':fav_uid'] = $uid;
}
$sql .= ' WHERE 1=1';
if ($cuisine !== '') {
    $sql .= ' AND r.cuisine_type = :cuisine';
    $params[':cuisine'] = $cuisine;
}
if ($dietary !== '') {
    $sql .= ' AND r.dietary_preference = :dietary';
    $params[':dietary'] = $dietary;
}
if ($difficulty !== '' && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
    $sql .= ' AND r.difficulty = :diff';
    $params[':diff'] = $difficulty;
}
if ($search !== '') {
    $sql .= ' AND (r.title LIKE :q OR r.description LIKE :q OR r.instructions LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
$sql .= ' ORDER BY r.is_featured DESC, r.title ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

try {
    log_user_interaction($pdo, $uid, 'recipe_browse', 'recipes', null, [
        'cuisine' => $cuisine,
        'dietary' => $dietary,
        'difficulty' => $difficulty,
        'q' => $search,
        'saved' => $savedOnly,
    ]);
} catch (Throwable) {
}

$favIds = [];
if ($uid !== null) {
    $f = $pdo->prepare('SELECT recipe_id FROM user_favorites WHERE user_id = ?');
    $f->execute([$uid]);
    $favIds = array_map('intval', array_column($f->fetchAll(), 'recipe_id'));
}

$qBuild = array_filter([
    'cuisine' => $cuisine,
    'dietary' => $dietary,
    'difficulty' => $difficulty,
    'q' => $search,
    'saved' => $savedOnly ? '1' : '',
], static fn ($v) => $v !== '' && $v !== null);
$returnUrl = 'recipes.php' . ($qBuild !== [] ? '?' . http_build_query($qBuild) : '');
$filterOnly = array_filter([
    'cuisine' => $cuisine,
    'dietary' => $dietary,
    'difficulty' => $difficulty,
    'q' => $search,
], static fn ($v) => $v !== '' && $v !== null);
$hrefSaved = 'recipes.php?' . http_build_query(array_merge($filterOnly, ['saved' => '1']));
$hrefAllSaved = 'recipes.php' . ($filterOnly !== [] ? '?' . http_build_query($filterOnly) : '');

$tmdbI = trim((string) ($_GET['tmdb_i'] ?? ''));
if ($tmdbI !== '' && !array_key_exists($tmdbI, MEALDB_INGREDIENT_FILTERS)) {
    $tmdbI = '';
}
$mealdbList = $tmdbI === '' ? mealdb_browse_many(48) : mealdb_filter_by_ingredient($tmdbI);

$mealViewId = trim((string) ($_GET['meal'] ?? ''));
$mealDetail = null;
$mealLookupFailed = false;
if ($mealViewId !== '' && preg_match('/^\d{1,10}$/', $mealViewId)) {
    $mealDetail = mealdb_lookup_by_id($mealViewId);
    if ($mealDetail === null) {
        $mealLookupFailed = true;
    }
} elseif ($mealViewId !== '') {
    $mealLookupFailed = true;
}

$mealdbBaseQuery = array_filter([
    'cuisine' => $cuisine,
    'dietary' => $dietary,
    'difficulty' => $difficulty,
    'q' => $search,
    'saved' => $savedOnly ? '1' : '',
], static fn ($v) => $v !== '' && $v !== null);
if ($tmdbI !== '') {
    $mealdbBaseQuery['tmdb_i'] = $tmdbI;
}
$mealdbBackQuery = $mealdbBaseQuery;
$mealdbBackHref = 'recipes.php' . ($mealdbBackQuery !== [] ? '?' . http_build_query($mealdbBackQuery) : '');

$cuisines = $pdo->query('SELECT DISTINCT cuisine_type FROM recipes ORDER BY cuisine_type')->fetchAll(PDO::FETCH_COLUMN);
$dietaries = $pdo->query('SELECT DISTINCT dietary_preference FROM recipes ORDER BY dietary_preference')->fetchAll(PDO::FETCH_COLUMN);

/** @var array<string, string> */
$recipeNavBase = [
    'cuisine' => $cuisine,
    'dietary' => $dietary,
    'difficulty' => $difficulty,
    'q' => $search,
    'saved' => $savedOnly ? '1' : '',
    'tmdb_i' => $tmdbI,
    'meal' => $mealViewId,
];

/**
 * @param array<string, string> $base
 * @param array<string, string> $overrides
 */
function recipes_page_url(array $base, array $overrides): string
{
    $m = array_merge($base, $overrides);
    $m = array_filter($m, static fn ($v) => $v !== '' && $v !== null);
    return 'recipes.php' . ($m !== [] ? '?' . http_build_query($m) : '');
}

$pageTitle = 'Recipe Collection — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="page-intro card">
    <p class="eyebrow">Explore</p>
    <h1>Recipe Collection</h1>
    <p class="lede">Search below, or tap a <strong>cuisine</strong>, <strong>diet</strong>, or <strong>difficulty</strong> to narrow the FoodFusion collection.</p>
    <form method="get" class="search-bar" role="search" aria-label="Search recipes">
        <input type="search" name="q" placeholder="Search titles, ingredients, methods…" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
        <?php if ($cuisine !== ''): ?><input type="hidden" name="cuisine" value="<?= htmlspecialchars($cuisine, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <?php if ($dietary !== ''): ?><input type="hidden" name="dietary" value="<?= htmlspecialchars($dietary, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <?php if ($difficulty !== ''): ?><input type="hidden" name="difficulty" value="<?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <?php if ($savedOnly): ?><input type="hidden" name="saved" value="1"><?php endif; ?>
        <?php if ($tmdbI !== ''): ?><input type="hidden" name="tmdb_i" value="<?= htmlspecialchars($tmdbI, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <?php if ($mealViewId !== ''): ?><input type="hidden" name="meal" value="<?= htmlspecialchars($mealViewId, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <button type="submit" class="btn primary">Search</button>
    </form>

    <div class="recipe-category-filters" aria-label="Recipe categories">
        <div class="recipe-filter-row">
            <span class="recipe-filter-heading" id="filter-cuisine">Cuisine</span>
            <div class="recipe-filter-pills" role="group" aria-labelledby="filter-cuisine">
                <a class="pill-btn<?= $cuisine === '' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(recipes_page_url($recipeNavBase, ['cuisine' => '']), ENT_QUOTES, 'UTF-8') ?>">All</a>
                <?php foreach ($cuisines as $c):
                    $c = (string) $c;
                    ?>
                    <a class="pill-btn<?= $cuisine === $c ? ' is-active' : '' ?>" href="<?= htmlspecialchars(recipes_page_url($recipeNavBase, ['cuisine' => $c]), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="recipe-filter-row">
            <span class="recipe-filter-heading" id="filter-dietary">Dietary</span>
            <div class="recipe-filter-pills" role="group" aria-labelledby="filter-dietary">
                <a class="pill-btn<?= $dietary === '' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(recipes_page_url($recipeNavBase, ['dietary' => '']), ENT_QUOTES, 'UTF-8') ?>">All</a>
                <?php foreach ($dietaries as $d):
                    $d = (string) $d;
                    ?>
                    <a class="pill-btn<?= $dietary === $d ? ' is-active' : '' ?>" href="<?= htmlspecialchars(recipes_page_url($recipeNavBase, ['dietary' => $d]), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="recipe-filter-row">
            <span class="recipe-filter-heading" id="filter-difficulty">Difficulty</span>
            <div class="recipe-filter-pills" role="group" aria-labelledby="filter-difficulty">
                <a class="pill-btn<?= $difficulty === '' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(recipes_page_url($recipeNavBase, ['difficulty' => '']), ENT_QUOTES, 'UTF-8') ?>">All</a>
                <?php foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $dk => $dl): ?>
                    <a class="pill-btn<?= $difficulty === $dk ? ' is-active' : '' ?>" href="<?= htmlspecialchars(recipes_page_url($recipeNavBase, ['difficulty' => $dk]), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dl, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="card mealdb-section" aria-labelledby="mealdb-heading">
    <div class="mealdb-head">
        <div>
            <p class="eyebrow">TheMealDB</p>
            <h2 id="mealdb-heading">Global recipe ideas</h2>
            <p class="lede small">Browse meals from the public <a href="https://www.themealdb.com/" target="_blank" rel="noopener noreferrer">TheMealDB</a> API. Pick an ingredient filter or mixed picks, then <strong>tap a dish</strong> to read the full recipe on this page.</p>
        </div>
    </div>
    <?php if ($mealLookupFailed): ?>
        <p class="notice warn" role="alert">That recipe could not be loaded. Try another dish or check your connection.</p>
    <?php endif; ?>
    <?php if ($mealDetail !== null):
        $ding = mealdb_meal_ingredient_lines($mealDetail);
        $mTitle = (string) ($mealDetail['strMeal'] ?? 'Recipe');
        $mThumb = (string) ($mealDetail['strMealThumb'] ?? '');
        $mCat = trim((string) ($mealDetail['strCategory'] ?? ''));
        $mArea = trim((string) ($mealDetail['strArea'] ?? ''));
        $mInstr = trim((string) ($mealDetail['strInstructions'] ?? ''));
        $mYt = trim((string) ($mealDetail['strYoutube'] ?? ''));
        ?>
        <article class="mealdb-detail" id="mealdb-recipe" aria-labelledby="mealdb-detail-title">
            <a class="mealdb-back link-arrow" href="<?= htmlspecialchars($mealdbBackHref, ENT_QUOTES, 'UTF-8') ?>">← Back to grid</a>
            <div class="mealdb-detail-layout">
                <?php if ($mThumb !== ''): ?>
                    <div class="mealdb-detail-image">
                        <img src="<?= htmlspecialchars($mThumb, ENT_QUOTES, 'UTF-8') ?>" alt="" width="600" height="600" loading="lazy">
                    </div>
                <?php endif; ?>
                <div class="mealdb-detail-body">
                    <p class="eyebrow">TheMealDB</p>
                    <h3 id="mealdb-detail-title"><?= htmlspecialchars($mTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                    <?php if ($mCat !== '' || $mArea !== ''): ?>
                        <p class="mealdb-meta">
                            <?php if ($mCat !== ''): ?><span><?= htmlspecialchars($mCat, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            <?php if ($mCat !== '' && $mArea !== ''): ?> · <?php endif; ?>
                            <?php if ($mArea !== ''): ?><span><?= htmlspecialchars($mArea, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($ding !== []): ?>
                        <h4 class="mealdb-subh">Ingredients</h4>
                        <ul class="mealdb-ingredients">
                            <?php foreach ($ding as $line): ?>
                                <li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($mInstr !== ''): ?>
                        <h4 class="mealdb-subh">Method</h4>
                        <div class="mealdb-instructions prose"><?= nl2br(htmlspecialchars($mInstr, ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                    <?php if ($mYt !== ''): ?>
                        <p class="mealdb-video"><a class="btn secondary" href="<?= htmlspecialchars($mYt, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Watch video</a></p>
                    <?php endif; ?>
                    <p class="mealdb-attrib small subtle">Data © TheMealDB. <a href="<?= htmlspecialchars(mealdb_meal_url((string) ($mealDetail['idMeal'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Open original page</a> (optional).</p>
                </div>
            </div>
        </article>
    <?php endif; ?>
    <div class="ingredient-pills" role="group" aria-label="Filter by main ingredient">
        <?php
        foreach (MEALDB_INGREDIENT_FILTERS as $key => $label):
            $pillQ = $mealdbBaseQuery;
            $pillQ['tmdb_i'] = $key;
            $pillHref = 'recipes.php?' . http_build_query($pillQ);
            ?>
            <a class="pill-btn<?= $tmdbI === $key ? ' is-active' : '' ?>" href="<?= htmlspecialchars($pillHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach;
        $allMealdb = $mealdbBaseQuery;
        unset($allMealdb['tmdb_i']);
        $allHref = 'recipes.php' . ($allMealdb !== [] ? '?' . http_build_query($allMealdb) : '');
        ?>
        <a class="pill-btn<?= $tmdbI === '' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($allHref, ENT_QUOTES, 'UTF-8') ?>">Mixed picks</a>
    </div>
    <?php if (count($mealdbList) === 0): ?>
        <p class="empty-hint">No recipes returned from the API. Try another ingredient or try again later.</p>
    <?php else: ?>
        <ul class="mealdb-grid">
            <?php foreach ($mealdbList as $m):
                $cardQ = $mealdbBaseQuery;
                $cardQ['meal'] = $m['idMeal'];
                $cardHref = 'recipes.php?' . http_build_query($cardQ) . '#mealdb-recipe';
                $isOpen = $mealViewId !== '' && (string) $m['idMeal'] === (string) $mealViewId;
                ?>
                <li<?= $isOpen ? ' class="mealdb-current"' : '' ?>>
                    <a class="mealdb-card" href="<?= htmlspecialchars($cardHref, ENT_QUOTES, 'UTF-8') ?>"<?= $isOpen ? ' aria-current="page"' : '' ?>>
                        <span class="mealdb-thumb-wrap">
                            <?php if ($m['strMealThumb'] !== ''): ?>
                                <img src="<?= htmlspecialchars($m['strMealThumb'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" width="300" height="300">
                            <?php endif; ?>
                        </span>
                        <span class="mealdb-title"><?= htmlspecialchars($m['strMeal'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mealdb-open">View recipe →</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="card recipe-collection-local">
    <div class="section-head">
        <h2>FoodFusion recipes</h2>
        <?php if ($uid !== null): ?>
            <a class="btn secondary<?= $savedOnly ? ' is-active' : '' ?>" href="<?= $savedOnly ? htmlspecialchars($hrefAllSaved, ENT_QUOTES, 'UTF-8') : htmlspecialchars($hrefSaved, ENT_QUOTES, 'UTF-8') ?>"><?= $savedOnly ? 'Show all' : 'Saved only' ?></a>
        <?php endif; ?>
    </div>
    <?php if (count($recipes) === 0): ?>
        <p class="empty-hint"><?= $savedOnly ? 'No saved recipes yet — tap the heart on any dish.' : ($search !== '' ? 'No recipes match your search.' : 'No recipes in the collection yet.') ?></p>
    <?php else: ?>
        <ul class="recipe-grid">
            <?php foreach ($recipes as $r): ?>
                <?php
                $instr = (string) ($r['instructions'] ?? '');
                $words = str_word_count(strip_tags($instr));
                $readMin = max(1, (int) ceil($words / 200));
                $isFav = in_array((int) $r['id'], $favIds, true);
                ?>
                <li class="recipe-card" id="recipe-<?= (int) $r['id'] ?>">
                    <div class="recipe-card-top">
                        <div class="recipe-card-badges">
                            <?php if (!empty($r['is_featured'])): ?>
                                <span class="pill pill-featured">Featured</span>
                            <?php endif; ?>
                            <span class="pill pill-time" title="Estimated prep / cook"><?= (int) ($r['prep_minutes'] ?? 0) ?> min</span>
                            <span class="pill pill-read"><?= $readMin ?> min read</span>
                        </div>
                        <?php if ($uid !== null): ?>
                            <form method="post" class="fav-form" aria-label="Save recipe">
                                <input type="hidden" name="toggle_favorite" value="1">
                                <input type="hidden" name="recipe_id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="fav-btn<?= $isFav ? ' is-saved' : '' ?>" title="<?= $isFav ? 'Remove from saved' : 'Save recipe' ?>" aria-pressed="<?= $isFav ? 'true' : 'false' ?>">
                                    <span class="fav-icon" aria-hidden="true"><?= $isFav ? '♥' : '♡' ?></span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="tags">
                        <span><?= htmlspecialchars($r['cuisine_type'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($r['dietary_preference'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="tag-difficulty tag-<?= htmlspecialchars($r['difficulty'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['difficulty'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                    <p class="recipe-desc"><?= htmlspecialchars((string) $r['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <details class="recipe-details">
                        <summary>View method</summary>
                        <div class="prose print-area"><?= nl2br(htmlspecialchars($instr, ENT_QUOTES, 'UTF-8')) ?></div>
                        <button type="button" class="btn ghost btn-print" data-print-target="recipe-<?= (int) $r['id'] ?>">Print this recipe</button>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
