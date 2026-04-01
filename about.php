<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle = 'About Us — FoodFusion';
require __DIR__ . '/includes/header.php';
?>

<div class="about-page">
    <section class="about-hero card">
        <p class="eyebrow">Our story</p>
        <h1>FoodFusion</h1>
        <p class="about-tagline">Where curious cooks meet real recipes — seasonal, practical, and made to share.</p>
        <p class="lede">We started from a simple idea: cooking is less about perfection and more about <strong>confidence</strong>. Whether you’re batching lunch for the week or trying a new spice, FoodFusion is your corner of the internet for ideas, skills, and community.</p>
    </section>

    <section class="about-pillars" aria-label="What we stand for">
        <article class="pillar-card">
            <span class="pillar-icon" aria-hidden="true">◇</span>
            <h2>Learn by doing</h2>
            <p>Step-by-step dishes, technique notes, and resources you can actually open on a Tuesday — not just scroll past on Sunday.</p>
        </article>
        <article class="pillar-card">
            <span class="pillar-icon" aria-hidden="true">◎</span>
            <h2>Cook together</h2>
            <p>The Community Cookbook turns your kitchen experiments into inspiration for someone else’s dinner. Everyone’s a teacher.</p>
        </article>
        <article class="pillar-card">
            <span class="pillar-icon" aria-hidden="true">✦</span>
            <h2>Think beyond the plate</h2>
            <p>Energy, waste, and seasonality matter. Our <a href="educational_resources.php">educational picks</a> connect what happens in the pan to what happens on the planet.</p>
        </article>
    </section>

    <section class="card readable about-story">
        <h2>Why we built this</h2>
        <p>Too many recipe sites overwhelm you with ads and autoplay. We wanted a calmer space: curated collections, honest difficulty labels, and a place to save what you’ll actually cook again.</p>
        <ul class="about-facts">
            <li><strong>Global flavours, local pace</strong> — filters for cuisine, diet, and skill level.</li>
            <li><strong>Global recipe ideas</strong> — TheMealDB integration for discovery, with full methods on-site.</li>
            <li><strong>Privacy first</strong> — see our <a href="privacy.php">privacy policy</a> and <a href="cookies.php">cookies</a> — no dark patterns.</li>
        </ul>
    </section>

    <section class="card about-team-section">
        <h2>Team (demo)</h2>
        <p class="lede small">A fictional crew for a real student project — swap names for your brief if needed.</p>
        <ul class="team-grid">
            <li class="team-card">
                <span class="team-avatar" aria-hidden="true">MC</span>
                <div>
                    <strong>Maya Chen</strong>
                    <span class="team-role">Editorial — ex pastry, forever optimising cake ratios.</span>
                </div>
            </li>
            <li class="team-card">
                <span class="team-avatar" aria-hidden="true">JA</span>
                <div>
                    <strong>Jordan Adeyemi</strong>
                    <span class="team-role">Community — supper clubs, moderation, welcome threads.</span>
                </div>
            </li>
            <li class="team-card">
                <span class="team-avatar" aria-hidden="true">SP</span>
                <div>
                    <strong>Samira Patel</strong>
                    <span class="team-role">Video — knife skills, camera angles, fewer band-aids.</span>
                </div>
            </li>
            <li class="team-card">
                <span class="team-avatar" aria-hidden="true">LG</span>
                <div>
                    <strong>Leo García</strong>
                    <span class="team-role">Sustainability — links kitchen habits to <a href="educational_resources.php">energy resources</a>.</span>
                </div>
            </li>
        </ul>
    </section>

    <section class="about-cta card">
        <h2>Questions or collabs?</h2>
        <p>We’d love to hear from clubs, schools, and home cooks building something similar.</p>
        <a class="btn primary" href="contact.php">Contact us</a>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
