<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-left">
        <div class="social-icons">
            <!-- Placeholders for social icons -->
            <a href="#" aria-label="Facebook"><span class="dashicons dashicons-facebook"></span></a>
            <a href="#" aria-label="Instagram"><span class="dashicons dashicons-instagram"></span></a>
            <a href="#" aria-label="Twitter"><span class="dashicons dashicons-twitter"></span></a>
        </div>
    </div>

    <div class="header-center">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo">
            <?php bloginfo( 'name' ); ?>
        </a>
    </div>

    <div class="header-right">
        <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
            <span class="dashicons dashicons-menu"></span>
        </button>

        <nav id="site-navigation" class="site-navigation">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'fallback_cb'    => false, // Don't show pages list if no menu assigned, keeps it clean
            ) );
            ?>
        </nav>
    </div>
</header>
