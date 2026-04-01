<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/interactions.php';

$pdo = db();
$flash = '';
$flashOk = true;
$viewId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'post') {
        require_login();
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['recipe_body'] ?? ''));
        $tips = trim((string) ($_POST['cooking_tips'] ?? ''));
        $notes = trim((string) ($_POST['experience_notes'] ?? ''));
        if ($title === '' || strlen($title) < 3) {
            $flash = 'Title must be at least 3 characters.';
            $flashOk = false;
        } elseif ($body === '' || strlen($body) < 20) {
            $flash = 'Please share a fuller recipe or story (at least 20 characters).';
            $flashOk = false;
        } else {
            $uid = current_user_id();
            $stmt = $pdo->prepare(
                'INSERT INTO community_recipes (user_id, title, recipe_body, cooking_tips, experience_notes) VALUES (:u,:t,:b,:tips,:notes)'
            );
            $stmt->execute([
                ':u' => $uid,
                ':t' => $title,
                ':b' => $body,
                ':tips' => $tips !== '' ? $tips : null,
                ':notes' => $notes !== '' ? $notes : null,
            ]);
            $newId = (int) $pdo->lastInsertId();
            log_user_interaction($pdo, $uid, 'community_post_form', 'community_recipe', $newId, ['title' => $title]);
            header('Location: community.php?id=' . $newId . '&posted=1');
            exit;
        }
    } elseif ($action === 'comment') {
        require_login();
        $rid = (int) ($_POST['community_recipe_id'] ?? 0);
        $text = trim((string) ($_POST['comment_text'] ?? ''));
        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
        if ($rid < 1 || $text === '' || strlen($text) < 2) {
            $flash = 'Please enter a comment.';
            $flashOk = false;
        } elseif ($rating < 0 || $rating > 5) {
            $flash = 'Rating must be between 1 and 5 if provided.';
            $flashOk = false;
        } else {
            $chk = $pdo->prepare('SELECT id FROM community_recipes WHERE id = :id LIMIT 1');
            $chk->execute([':id' => $rid]);
            if (!$chk->fetch()) {
                $flash = 'Recipe not found.';
                $flashOk = false;
            } else {
                $rVal = $rating >= 1 && $rating <= 5 ? $rating : null;
                $stmt = $pdo->prepare(
                    'INSERT INTO community_comments (community_recipe_id, user_id, comment_text, rating) VALUES (:r,:u,:c,:rate)'
                );
                $stmt->execute([
                    ':r' => $rid,
                    ':u' => current_user_id(),
                    ':c' => $text,
                    ':rate' => $rVal,
                ]);
                log_user_interaction($pdo, current_user_id(), 'community_comment', 'community_recipe', $rid, null);
                header('Location: community.php?id=' . $rid . '&commented=1');
                exit;
            }
        }
    }
}

if (isset($_GET['posted'])) {
    $flash = 'Your recipe was published.';
    $flashOk = true;
}
if (isset($_GET['commented'])) {
    $flash = 'Thanks — your comment was added.';
    $flashOk = true;
}

$single = null;
$comments = [];
$list = [];
if ($viewId > 0) {
    $st = $pdo->prepare(
        'SELECT c.*, u.first_name, u.last_name FROM community_recipes c JOIN users u ON u.id = c.user_id WHERE c.id = :id'
    );
    $st->execute([':id' => $viewId]);
    $single = $st->fetch();
    if ($single) {
        $cc = $pdo->prepare(
            'SELECT n.*, u.first_name, u.last_name FROM community_comments n JOIN users u ON u.id = n.user_id WHERE n.community_recipe_id = :r ORDER BY n.created_at ASC'
        );
        $cc->execute([':r' => $viewId]);
        $comments = $cc->fetchAll();
    }
}

if (!$single) {
    $list = $pdo->query(
        'SELECT c.id, c.title, c.created_at, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM community_comments x WHERE x.community_recipe_id = c.id) AS comment_count
        FROM community_recipes c JOIN users u ON u.id = c.user_id ORDER BY c.created_at DESC'
    )->fetchAll();
}

$pageTitle = 'Community Cookbook — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h1>Community Cookbook</h1>
    <p class="lede">Share favourite recipes, tips, and kitchen stories. Log in to post or comment.</p>
    <?php if ($flash !== ''): ?>
        <p class="notice <?= $flashOk ? 'success' : 'error' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</section>

<?php if ($single): ?>
    <article class="card">
        <h2><?= htmlspecialchars($single['title'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="meta">By <?= htmlspecialchars($single['first_name'] . ' ' . $single['last_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($single['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
        <h3>Recipe / write-up</h3>
        <div class="prose"><?= nl2br(htmlspecialchars($single['recipe_body'], ENT_QUOTES, 'UTF-8')) ?></div>
        <?php if ($single['cooking_tips']): ?>
            <h3>Cooking tips</h3>
            <div class="prose"><?= nl2br(htmlspecialchars((string) $single['cooking_tips'], ENT_QUOTES, 'UTF-8')) ?></div>
        <?php endif; ?>
        <?php if ($single['experience_notes']): ?>
            <h3>Experience</h3>
            <div class="prose"><?= nl2br(htmlspecialchars((string) $single['experience_notes'], ENT_QUOTES, 'UTF-8')) ?></div>
        <?php endif; ?>
        <p><a href="community.php">← All community posts</a></p>
    </article>

    <section class="card">
        <h2>Comments &amp; ratings</h2>
        <?php if (count($comments) === 0): ?>
            <p>No comments yet — be the first.</p>
        <?php else: ?>
            <ul class="comment-list">
                <?php foreach ($comments as $cm): ?>
                    <li>
                        <strong><?= htmlspecialchars($cm['first_name'] . ' ' . $cm['last_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($cm['rating']): ?>
                            <span class="rating" aria-label="Rating <?= (int) $cm['rating'] ?> out of 5"><?= str_repeat('★', (int) $cm['rating']) ?><?= str_repeat('☆', 5 - (int) $cm['rating']) ?></span>
                        <?php endif; ?>
                        <time datetime="<?= htmlspecialchars($cm['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cm['created_at'], ENT_QUOTES, 'UTF-8') ?></time>
                        <p><?= nl2br(htmlspecialchars($cm['comment_text'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (current_user_id() !== null): ?>
            <form method="post" class="stack">
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="community_recipe_id" value="<?= (int) $single['id'] ?>">
                <label>Comment <textarea name="comment_text" required rows="3" maxlength="2000"></textarea></label>
                <label>Rating (optional)
                    <select name="rating">
                        <option value="0">—</option>
                        <option value="5">5 — excellent</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </label>
                <button type="submit" class="btn primary">Post comment</button>
            </form>
        <?php else: ?>
            <p><a href="login.php">Log in</a> to leave a comment or rating.</p>
        <?php endif; ?>
    </section>
<?php else: ?>
    <?php if ($viewId > 0): ?>
        <p class="notice error">That post was not found.</p>
    <?php endif; ?>

    <section class="card">
        <h2>Latest from the community</h2>
        <?php if (empty($list)): ?>
            <p>No submissions yet. Log in and add the first one.</p>
        <?php else: ?>
            <ul class="recipe-grid simple">
                <?php foreach ($list as $row): ?>
                    <li>
                        <a href="community.php?id=<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></a>
                        <span class="meta"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $row['comment_count'] ?> comments</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if (current_user_id() !== null): ?>
        <section class="card">
            <h2>Share with the community</h2>
            <p>Use this form to publish your recipe or story with the community.</p>
            <form method="post" class="stack">
                <input type="hidden" name="action" value="post">
                <label>Title <input type="text" name="title" required maxlength="200"></label>
                <label>Recipe / story <textarea name="recipe_body" required rows="6" minlength="20"></textarea></label>
                <label>Cooking tips <textarea name="cooking_tips" rows="3"></textarea></label>
                <label>Your experience <textarea name="experience_notes" rows="3" placeholder="What worked, what you would change…"></textarea></label>
                <button type="submit" class="btn primary">Publish</button>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <p><a href="login.php">Log in</a> or <a href="register.php">register</a> to publish and comment.</p>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
