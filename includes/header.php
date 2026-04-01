<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (!isset($pageTitle)) {
    $pageTitle = 'FoodFusion';
}
$displayName = null;
$loggedIn = current_user_id() !== null;
if (!isset($ffRequestStart)) {
    $ffRequestStart = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
}
$currentPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
if (isset($_SESSION['user_first_name'])) {
    $displayName = (string) $_SESSION['user_first_name'];
}
$logoHref = $loggedIn ? 'index.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="ff-api-base" content="">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#c2410c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="shortcut icon" href="assets/favicon.svg">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script>
    (function(){try{var t=localStorage.getItem('ff-theme');var m=document.querySelector('meta[name="theme-color"]');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);if(m)m.setAttribute('content',t==='dark'?'#1c1917':'#c2410c');}}catch(e){}})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="nav-backdrop" id="navBackdrop" aria-hidden="true"></div>
<header class="site-header">
    <div class="inner">
        <a class="logo" href="<?= htmlspecialchars($logoHref, ENT_QUOTES, 'UTF-8') ?>">
            <span class="logo-mark" aria-hidden="true">FF</span>
            <span class="logo-wordmark">
                <span class="logo-main">FoodFusion</span>
                <span class="logo-sub">Kitchen Club</span>
            </span>
        </a>
        <nav id="site-nav" class="site-nav" aria-label="Main">
            <ul>
                <?php if ($loggedIn): ?>
                    <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'is-active' : '' ?>" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>Home</a></li>
                    <li><a href="about.php" class="<?= $currentPage === 'about.php' ? 'is-active' : '' ?>" <?= $currentPage === 'about.php' ? 'aria-current="page"' : '' ?>>About Us</a></li>
                    <li><a href="recipes.php" class="<?= $currentPage === 'recipes.php' ? 'is-active' : '' ?>" <?= $currentPage === 'recipes.php' ? 'aria-current="page"' : '' ?>>Recipe Collection</a></li>
                    <li><a href="community.php" class="<?= $currentPage === 'community.php' ? 'is-active' : '' ?>" <?= $currentPage === 'community.php' ? 'aria-current="page"' : '' ?>>Community Cookbook</a></li>
                    <li><a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'is-active' : '' ?>" <?= $currentPage === 'contact.php' ? 'aria-current="page"' : '' ?>>Contact Us</a></li>
                    <li><a href="culinary_resources.php" class="<?= $currentPage === 'culinary_resources.php' ? 'is-active' : '' ?>" <?= $currentPage === 'culinary_resources.php' ? 'aria-current="page"' : '' ?>>Culinary Resources</a></li>
                    <li><a href="educational_resources.php" class="<?= $currentPage === 'educational_resources.php' ? 'is-active' : '' ?>" <?= $currentPage === 'educational_resources.php' ? 'aria-current="page"' : '' ?>>Educational Resources</a></li>
                    <li><a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'is-active' : '' ?>" <?= $currentPage === 'profile.php' ? 'aria-current="page"' : '' ?>>My Profile</a></li>
                    <li><span class="hi">Hi, <?= htmlspecialchars($displayName ?? '', ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li><a href="logout.php">Log out</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="<?= $currentPage === 'login.php' ? 'is-active' : '' ?>" <?= $currentPage === 'login.php' ? 'aria-current="page"' : '' ?>>Log in</a></li>
                    <li><a href="register.php" class="<?= $currentPage === 'register.php' ? 'is-active' : '' ?>" <?= $currentPage === 'register.php' ? 'aria-current="page"' : '' ?>>Sign up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <button type="button" class="theme-toggle" id="themeToggle" title="Toggle theme" aria-label="Toggle light or dark theme">
            <span class="theme-icon theme-icon-sun" aria-hidden="true">☀</span>
            <span class="theme-icon theme-icon-moon" aria-hidden="true">☾</span>
        </button>
        <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav" id="navToggle" aria-label="Open menu">
            <span class="nav-toggle-bars" aria-hidden="true"></span>
            <span class="nav-toggle-label">Menu</span>
        </button>
    </div>
</header>
<main id="main" class="site-main">
