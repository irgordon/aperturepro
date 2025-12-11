<?php
/**
 * The front page template file
 */

get_header(); ?>

<!-- Hero Section -->
<?php
$hero_bg = '';
if ( has_post_thumbnail() ) {
    $hero_bg = 'background-image: url(' . get_the_post_thumbnail_url( null, 'full' ) . ');';
}
?>
<section class="hero-section" style="<?php echo esc_attr( $hero_bg ); ?>">
    <div class="hero-content">
        <blockquote class="hero-quote">
            "Capturing moments that last a lifetime."
        </blockquote>
        <!-- CTA Button -->
        <a href="https://calendar.google.com/calendar/u/0/appointments/schedules/AcZssZE..." target="_blank" class="btn-cta">Book A Session</a>
    </div>
</section>

<!-- Lead Capture Section -->
<section class="lead-capture-section">
    <h2>Let's Start Your Journey</h2>
    <p>Sign up to receive our pricing guide and start your project.</p>

    <div class="lead-form-wrapper">
        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="lead-form">
            <input type="hidden" name="action" value="ap_submit_lead">
            <?php wp_nonce_field( 'ap_lead_submit', 'ap_lead_nonce' ); ?>

            <input type="text" name="client_name" placeholder="Your Name" required>
            <input type="email" name="client_email" placeholder="Your Email Address" required>

            <!-- Hidden defaults for the simple form -->
            <input type="hidden" name="shoot_type" value="Portrait">
            <input type="hidden" name="shoot_date" value="<?php echo date('Y-m-d', strtotime('+1 week')); ?>">

            <button type="submit">Get Started</button>
        </form>
    </div>
</section>

<!-- Portfolio Section -->
<section class="portfolio-section">
    <h2 style="text-align: center; margin-bottom: 2rem;">Recent Works</h2>
    <div class="portfolio-grid">
        <?php
        $portfolio_args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => 9,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        $portfolio_query = new WP_Query( $portfolio_args );

        if ( $portfolio_query->have_posts() ) :
            while ( $portfolio_query->have_posts() ) : $portfolio_query->the_post();
                ?>
                <div class="portfolio-item">
                    <?php echo wp_get_attachment_image( get_the_ID(), 'medium_large' ); ?>
                </div>
                <?php
            endwhile;
            wp_reset_postdata();
        else :
            // Fallback if no images exist yet
            for ($i = 0; $i < 9; $i++) {
                echo '<div class="portfolio-item" style="background: #ddd; display: flex; align-items: center; justify-content: center; color: #999;">Image ' . ($i+1) . '</div>';
            }
        endif;
        ?>
    </div>
</section>

<?php get_footer(); ?>
