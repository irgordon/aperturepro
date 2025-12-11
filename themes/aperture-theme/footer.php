<div class="spacer"></div>

<footer class="site-footer">
    <div class="footer-copyright">
        &copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.
    </div>
    <div class="footer-legal">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'legal',
            'container'      => false,
            'depth'          => 1,
            'fallback_cb'    => false,
        ) );
        ?>
    </div>
</footer>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.site-navigation');

    if (menuToggle && navigation) {
        menuToggle.addEventListener('click', function() {
            const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', !isExpanded);
            navigation.classList.toggle('active');
        });
    }
});
</script>

</body>
</html>
