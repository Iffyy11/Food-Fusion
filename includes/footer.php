</main>
<div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>
<button type="button" class="scroll-top-fab" id="scrollTopFab" aria-label="Back to top" title="Back to top">
    <span aria-hidden="true">↑</span>
</button>
<footer class="site-footer">
    <div class="inner footer-grid">
        <div>
            <strong>FoodFusion</strong>
            <p>Home cooking, shared recipes, and a curious food community.</p>
        </div>
        <div>
            <strong>Privacy &amp; cookies</strong>
            <ul class="footer-links">
                <li><a href="privacy.php">Privacy policy</a></li>
                <li><a href="cookies.php">Cookie information</a></li>
                <li><a href="copyright.php">Copyright</a></li>
            </ul>
        </div>
        <div>
            <strong>Social</strong>
            <?php
            $social_ul_class = 'social social-icons';
            require __DIR__ . '/social_icons.php';
            ?>
        </div>
    </div>
    <p class="copy">© <?= date('Y') ?> FoodFusion — student project (NCC 2183-1)</p>
</footer>
<?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
    <?php
    $ffElapsedMs = (microtime(true) - ($ffRequestStart ?? microtime(true))) * 1000;
    ?>
    <div style="position:fixed;right:12px;bottom:12px;z-index:9999;padding:6px 10px;border-radius:8px;background:#111827;color:#f9fafb;font:600 12px/1.2 system-ui,sans-serif;opacity:.92;">
        PHP render: <?= number_format($ffElapsedMs, 1) ?> ms
    </div>
<?php endif; ?>
<script src="assets/js/main.js" defer></script>
</body>
</html>
