<?php
declare(strict_types=1);

/**
 * Task 1 — Database setup: tables with primary keys, foreign keys, and appropriate data types.
 * Run once in the browser after configuring includes/config.php.
 */
require_once __DIR__ . '/includes/config_load.php';

header('Content-Type: text/html; charset=utf-8');

$dsn = sprintf('mysql:host=%s;port=%d;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . str_replace('`', '``', DB_NAME) . '`');

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_locked (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS recipes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    description TEXT,
    instructions TEXT NOT NULL,
    cuisine_type VARCHAR(80) NOT NULL,
    dietary_preference VARCHAR(80) NOT NULL,
    difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'easy',
    prep_minutes SMALLINT UNSIGNED DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED DEFAULT NULL,
    UNIQUE KEY uk_recipes_slug (slug),
    KEY idx_recipes_cuisine (cuisine_type),
    KEY idx_recipes_dietary (dietary_preference),
    KEY idx_recipes_difficulty (difficulty),
    CONSTRAINT fk_recipes_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    try {
        $pdo->exec('ALTER TABLE recipes ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0');
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) !== 1060) {
            throw $e;
        }
    }

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS user_favorites (
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    KEY idx_fav_recipe (recipe_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(800) NOT NULL,
    event_datetime DATETIME NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_events_when (event_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS news_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    summary VARCHAR(600) NOT NULL,
    link VARCHAR(500) DEFAULT NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS culinary_resources (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(600) NOT NULL,
    resource_type ENUM('card','video','tutorial') NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS educational_resources (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description VARCHAR(600) NOT NULL,
    resource_type ENUM('pdf','infographic','video') NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS community_recipes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    recipe_body TEXT NOT NULL,
    cooking_tips TEXT,
    experience_notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_community_user (user_id),
    CONSTRAINT fk_community_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS community_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    community_recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment_text VARCHAR(2000) NOT NULL,
    rating TINYINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cc_recipe (community_recipe_id),
    KEY idx_cc_user (user_id),
    CONSTRAINT fk_cc_recipe FOREIGN KEY (community_recipe_id) REFERENCES community_recipes (id) ON DELETE CASCADE,
    CONSTRAINT fk_cc_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL,
    category ENUM('general','recipe_request','feedback') NOT NULL DEFAULT 'general',
    subject VARCHAR(200) NOT NULL,
    message VARCHAR(4000) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS newsletter_signups (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    first_name VARCHAR(80) DEFAULT NULL,
    last_name VARCHAR(80) DEFAULT NULL,
    signed_up_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS user_interactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action_type VARCHAR(64) NOT NULL,
    entity_type VARCHAR(64) DEFAULT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    meta_json TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ui_user (user_id),
    KEY idx_ui_action (action_type),
    CONSTRAINT fk_ui_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    // Seed curated content
    $pdo->exec("INSERT IGNORE INTO news_items (id, title, summary, link, published_at) VALUES
        (1, 'Seasonal spotlight: citrus', 'Bright winter salads and marmalade tips from our test kitchen.', 'recipes.php?cuisine=Mediterranean', NOW()),
        (2, 'Trend: one-pot comfort', 'Readers are saving time with layered stews and sheet-pan dinners.', 'recipes.php?difficulty=easy', NOW()),
        (3, 'Community pick: vegan bakes', 'Top-rated egg-free cakes from the Cookbook this month.', 'community.php', NOW())");

    $pdo->exec("INSERT IGNORE INTO events (id, title, description, event_datetime, image_url) VALUES
        (1, 'Live: Knife skills basics', 'Chef-led demo covering safe chopping and julienne.', DATE_ADD(NOW(), INTERVAL 5 DAY), NULL),
        (2, 'Weekend bread workshop', 'Sourdough starter care and shaping loaves by hand.', DATE_ADD(NOW(), INTERVAL 12 DAY), NULL),
        (3, 'Global spices tasting', 'Explore aromatics from North Africa and South Asia.', DATE_ADD(NOW(), INTERVAL 20 DAY), NULL)");

    $pdo->exec("INSERT IGNORE INTO recipes (id, title, slug, description, instructions, cuisine_type, dietary_preference, difficulty, prep_minutes, is_featured) VALUES
        (1, 'Herb roasted chicken', 'herb-roasted-chicken', 'Classic Sunday roast with lemon and thyme.', '1) Season chicken. 2) Roast at 190°C until juices run clear. 3) Rest before carving.', 'European', 'high-protein', 'easy', 90, 1),
        (2, 'Vegetable tagine', 'vegetable-tagine', 'Aromatic North African stew.', '1) Sauté onions. 2) Add spices, veg, tomatoes. 3) Simmer 35m; serve with couscous.', 'North African', 'vegan', 'medium', 50, 1),
        (3, 'Miso ramen bowl', 'miso-ramen-bowl', 'Quick umami broth with greens.', '1) Simmer miso broth. 2) Cook noodles. 3) Assemble with toppings.', 'Japanese', 'vegetarian', 'medium', 35, 1),
        (4, 'Quinoa power salad', 'quinoa-power-salad', 'Protein-rich lunch box favourite.', '1) Cook quinoa. 2) Chop veg. 3) Dress with lemon and olive oil.', 'International', 'gluten-free', 'easy', 25, 0),
        (5, 'Beef birria tacos', 'beef-birria-tacos', 'Slow-cooked spiced beef for dipping.', '1) Braise beef with chillies. 2) Shred. 3) Crisp tacos; dip in consommé.', 'Mexican', 'high-protein', 'hard', 180, 1),
        (6, 'Berry oat crumble', 'berry-oat-crumble', 'Simple dessert with frozen berries.', '1) Toss berries with sugar. 2) Top with oat crumble. 3) Bake until golden.', 'British', 'vegetarian', 'easy', 40, 0)");

    $pdo->exec('UPDATE recipes SET is_featured = 1 WHERE id IN (1,2,3,5)');

    $pdo->exec("INSERT IGNORE INTO culinary_resources (id, title, description, resource_type, file_url, sort_order) VALUES
        (1, 'Printable recipe card — pasta basics', 'A4 card with ratios and timings.', 'card', 'downloads/sample-recipe-card.txt', 1),
        (2, 'Video: mastering roux', 'Foundation for sauces (placeholder link).', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 2),
        (3, 'Tutorial: knife grip PDF', 'Grip and motion diagrams.', 'tutorial', 'downloads/sample-tutorial.txt', 3),
        (4, 'Sheet-pan & oven timing guide', 'Rough times for veg and proteins at 200°C — adjust for your oven.', 'card', 'downloads/sample-recipe-card.txt', 4),
        (5, 'Building flavour: umami basics', 'Tomato, miso, parmesan — where savoury depth comes from.', 'tutorial', 'downloads/sample-tutorial.txt', 5),
        (6, 'Video: egg doneness from soft to hard', 'Visual cues for boiling and poaching.', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 6),
        (7, 'Mise en place checklist', 'Prep-before-you-cook workflow for calmer weeknights.', 'card', 'downloads/sample-recipe-card.txt', 7),
        (8, 'Mother sauces overview', 'Quick map from béchamel to velouté (reading notes).', 'tutorial', 'downloads/sample-tutorial.txt', 8),
        (9, 'Kitchen safety one-pager', 'Temperatures, boards, and hand-washing reminders.', 'card', 'downloads/kitchen-safety-basics.txt', 9)");

    $pdo->exec("INSERT IGNORE INTO educational_resources (id, title, description, resource_type, file_url, sort_order) VALUES
        (1, 'Home solar basics infographic', 'How rooftop PV offsets cooking-related energy use.', 'infographic', 'downloads/sample-renewable-infographic.txt', 1),
        (2, 'Renewable energy primer PDF', 'Short reading on grid mix and efficiency.', 'pdf', 'downloads/sample-renewable-guide.txt', 2),
        (3, 'Induction vs gas — efficiency clip', 'Video notes on kitchen energy choices.', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 3),
        (4, 'Cutting food waste at home', 'Plan-ahead shopping, FIFO in the fridge, and creative leftovers.', 'pdf', 'downloads/sample-renewable-guide.txt', 4),
        (5, 'Reading nutrition labels', 'Sugar, salt, and portion size — what to scan first.', 'infographic', 'downloads/sample-renewable-infographic.txt', 5),
        (6, 'Composting in small spaces', 'Bokashi and counter bins for flats without a garden.', 'video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 6),
        (7, 'Smart meters & efficient cooking', 'Why batching oven use and shorter boils can show on your bill.', 'pdf', 'downloads/sample-renewable-guide.txt', 7),
        (8, 'Seasonal eating starter', 'Why local seasons matter for flavour and footprint.', 'pdf', 'downloads/seasonal-eating-intro.txt', 8),
        (9, 'Hydration & kitchen comfort', 'Staying alert during long cooks — breaks and ventilation.', 'infographic', 'downloads/sample-renewable-infographic.txt', 9)");

    echo '<p>Database <strong>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</strong> is ready with FoodFusion tables.</p>';
    echo '<p><a href="index.php">Open FoodFusion home</a></p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<p>Setup failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
