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
<script src="assets/js/main.js" defer></script>
</body>
</html>
