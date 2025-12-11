<?php get_header(); ?>

<div class="ap-gallery-container">
    <?php
    if ( post_password_required() ) {
        echo '<div class="password-form-wrapper" style="text-align:center; padding: 50px;">';
        echo get_the_password_form();
        echo '</div>';
        get_footer();
        exit;
    }
    ?>

    <header class="ap-gallery-header">
        <h1><?php the_title(); ?></h1>
        <?php if(has_excerpt()): ?>
            <div class="gallery-description"><?php the_excerpt(); ?></div>
        <?php endif; ?>
    </header>

    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" id="ap-gallery-form">
        <input type="hidden" name="action" value="ap_submit_gallery_selection">
        <input type="hidden" name="gallery_id" value="<?php echo get_the_ID(); ?>">
        <?php wp_nonce_field( 'ap_gallery_submit', 'ap_gallery_nonce' ); ?>

        <div class="ap-photo-grid">
            <?php
            $images = get_attached_media( 'image', get_the_ID() );
            
            if ( $images ) :
                foreach ( $images as $img ) :
                    $img_id = $img->ID;
                    // Use standard WP image URL for reliability in this demo,
                    // though production might use the protected URL logic.
                    $img_src = wp_get_attachment_image_url($img_id, 'medium_large');
                    $filename = basename( get_attached_file( $img_id ) );
                    
                    ?>
                    <div class="ap-photo-card" data-id="<?php echo esc_attr($img_id); ?>">
                        <div class="ap-img-wrapper">
                            <img src="<?php echo esc_url( $img_src ); ?>" loading="lazy" alt="Proof #<?php echo $img_id; ?>">
                        </div>
                        <div class="ap-card-footer">
                            <span class="ap-img-id">#<?php echo $img_id; ?></span>
                            <label class="ap-checkbox-wrapper">
                                <input type="checkbox" name="selected_images[]" value="<?php echo esc_attr( $img_id ); ?>">
                                <span class="ap-custom-checkmark"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach;
            else :
                echo '<p>No images found in this gallery.</p>';
            endif;
            ?>
        </div>

        <div class="ap-gallery-actions sticky-footer">
            <div class="left-actions">
                <a href="#" id="select-all">Select All</a>
                <span class="sep">|</span>
                <a href="#" id="deselect-all">Deselect All</a>
            </div>
            <div class="right-actions">
                <span id="selection-count">0 Selected</span>
                <button type="submit" class="btn-send">Send Selection</button>
            </div>
        </div>
    </form>
</div>

<style>
    .ap-gallery-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        padding-bottom: 80px; /* Space for footer */
    }
    .ap-gallery-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .ap-photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    .ap-photo-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 4px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .ap-photo-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .ap-img-wrapper img {
        width: 100%;
        height: auto;
        display: block;
    }
    .ap-card-footer {
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        background: #fafafa;
    }
    .ap-img-id {
        font-weight: bold;
        color: #777;
    }

    /* Custom Checkbox */
    .ap-checkbox-wrapper {
        position: relative;
        display: inline-block;
        width: 24px;
        height: 24px;
        cursor: pointer;
    }
    .ap-checkbox-wrapper input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .ap-custom-checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 24px;
        width: 24px;
        background-color: #fff;
        border: 2px solid #ddd;
        border-radius: 4px;
    }
    .ap-photo-card.selected .ap-card-footer {
        background-color: #e6f7ff; /* Light blue bg when selected */
    }
    .ap-photo-card.selected .ap-custom-checkmark {
        background-color: #2ea2cc;
        border-color: #2ea2cc;
    }
    .ap-custom-checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }
    .ap-photo-card.selected .ap-custom-checkmark:after {
        display: block;
    }
    .ap-custom-checkmark:after {
        left: 8px;
        top: 4px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    /* Sticky Footer */
    .sticky-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        border-top: 1px solid #ccc;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        z-index: 1000;
    }
    .left-actions a {
        color: #2ea2cc;
        text-decoration: none;
        font-weight: 500;
        margin-right: 10px;
    }
    .left-actions .sep {
        color: #ccc;
        margin-right: 10px;
    }
    .btn-send {
        background: #2ea2cc;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
    }
    .btn-send:hover {
        background: #1e87ad;
    }
    #selection-count {
        margin-right: 20px;
        color: #777;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.ap-photo-card');
    const selectAllBtn = document.getElementById('select-all');
    const deselectAllBtn = document.getElementById('deselect-all');
    const countSpan = document.getElementById('selection-count');

    function updateCount() {
        const checked = document.querySelectorAll('input[type="checkbox"]:checked');
        countSpan.innerText = checked.length + ' Selected';

        // Update visual state of cards
        cards.forEach(card => {
            const cb = card.querySelector('input[type="checkbox"]');
            if(cb.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }

    // Toggle on card click (optional ux improvement)
    cards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');

        // Allow clicking the image to toggle
        card.querySelector('.ap-img-wrapper').addEventListener('click', function(e) {
            checkbox.checked = !checkbox.checked;
            updateCount();
        });

        checkbox.addEventListener('change', updateCount);
    });

    selectAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
        updateCount();
    });

    deselectAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        updateCount();
    });
});
</script>

<?php get_footer(); ?>
