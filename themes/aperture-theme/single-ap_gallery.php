<?php get_header(); ?>

<div class="ap-gallery-container">
    <?php
    if ( post_password_required() ) {
        echo get_the_password_form(); // Built-in WP password protection
        get_footer();
        exit;
    }
    ?>

    <header class="ap-gallery-header">
        <h1><?php the_title(); ?></h1>
        <p>Please select your favorites for retouching.</p>
    </header>

    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" id="ap-gallery-form">
        <input type="hidden" name="action" value="ap_submit_gallery_selection">
        <input type="hidden" name="gallery_id" value="<?php echo get_the_ID(); ?>">
        <?php wp_nonce_field( 'ap_gallery_submit', 'ap_gallery_nonce' ); ?>

        <div class="ap-photo-grid">
            <?php
            // Retrieve images attached to this Gallery Post
            // In a real usage, you'd use a media uploader field. 
            // Here we assume standard WP Attachments.
            $images = get_attached_media( 'image', get_the_ID() );
            
            if ( $images ) :
                foreach ( $images as $img ) :
                    $img_id = $img->ID;
                    // Use the rewrite rule we created in class-gallery-proof.php
                    // URL structure: /proofs/{gallery_id}/{image_filename}
                    $filename = basename( get_attached_file( $img_id ) );
                    // Strip extension for cleaner processing if needed, but keeping simple here
                    $filename_base = pathinfo($filename, PATHINFO_FILENAME);
                    
                    $proof_url = home_url( '/proofs/' . get_the_ID() . '/' . $filename_base . '.jpg' );
                    ?>
                    <div class="ap-photo-card">
                        <label>
                            <div class="ap-img-wrapper">
                                <img src="<?php echo esc_url( $proof_url ); ?>" loading="lazy" alt="Proof">
                                <div class="ap-overlay">
                                    <input type="checkbox" name="selected_images[]" value="<?php echo esc_attr( $img_id ); ?>">
                                    <span class="ap-check-icon">✔</span>
                                </div>
                            </div>
                            <span class="ap-img-name"><?php echo esc_html( $filename_base ); ?></span>
                        </label>
                    </div>
                <?php endforeach;
            else :
                echo '<p>No images found in this gallery.</p>';
            endif;
            ?>
        </div>

        <div class="ap-gallery-footer sticky-footer">
            <span id="selection-count">0 images selected</span>
            <button type="submit" class="button-primary">Finalize Selection</button>
        </div>
    </form>
</div>

<style>
    /* Minimal CSS for Grid */
    .ap-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .ap-img-wrapper { position: relative; cursor: pointer; }
    .ap-img-wrapper img { width: 100%; display: block; border-radius: 4px; }
    .ap-overlay { position: absolute; top: 10px; right: 10px; }
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 20px; border-top: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
    /* Hide actual checkbox, style the icon */
    input[type="checkbox"] { transform: scale(1.5); }
</style>

<script>
    // Simple counter script
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const countSpan = document.getElementById('selection-count');
    
    checkboxes.forEach(box => {
        box.addEventListener('change', () => {
            const count = document.querySelectorAll('input[type="checkbox"]:checked').length;
            countSpan.innerText = count + ' images selected';
        });
    });
</script>

<?php get_footer(); ?>
