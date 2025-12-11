<?php get_header(); ?>

<div class="ap-contract-container">
    <?php while ( have_posts() ) : the_post(); ?>
        
        <header class="ap-contract-header">
            <h1><?php the_title(); ?></h1>
            <p>Prepared for: <?php echo esc_html( get_post_meta( get_the_ID(), '_ap_client_name', true ) ); ?></p>
        </header>

        <div class="ap-contract-body">
            <?php the_content(); ?>
        </div>

        <div class="ap-signature-section">
            <?php if ( get_post_meta( get_the_ID(), '_ap_contract_signed', true ) ) : ?>
                
                <div class="ap-signed-success">
                    <h3>Contract Signed</h3>
                    <p>Signed on: <?php echo esc_html( get_post_meta( get_the_ID(), '_ap_signed_date', true ) ); ?></p>
                    <img src="<?php echo esc_url( get_post_meta( get_the_ID(), '_ap_signature_image', true ) ); ?>" alt="Client Signature">
                </div>

            <?php else : ?>

                <h3>Acceptance & Signature</h3>
                <form id="ap-contract-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="ap_sign_contract">
                    <input type="hidden" name="contract_id" value="<?php echo get_the_ID(); ?>">
                    
                    <label><input type="checkbox" required> I have read and agree to the terms above.</label>
                    
                    <div class="signature-pad-wrapper">
                        <canvas id="signature-pad" width="400" height="200"></canvas>
                    </div>
                    <input type="hidden" name="signature_data" id="signature_data">
                    
                    <button type="submit" class="button-primary">Sign & Confirm</button>
                </form>

            <?php endif; ?>
        </div>

    <?php endwhile; ?>
</div>

<script>
    // Simple JS to handle the canvas drawing would go in assets/js/contract.js
    var canvas = document.getElementById('signature-pad');
    if(canvas){
        var signaturePad = new SignaturePad(canvas);
        document.getElementById('ap-contract-form').addEventListener('submit', function () {
            document.getElementById('signature_data').value = signaturePad.toDataURL();
        });
    }
</script>

<?php get_footer(); ?>
