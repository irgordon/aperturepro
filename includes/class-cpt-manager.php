<?php
class Aperture_CPT_Manager {

    public function init() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_data' ) );
    }

    public function register_post_types() {
        // 1. Customer Entity
        register_post_type( 'ap_customer', array(
            'labels' => array( 'name' => 'Customers', 'singular_name' => 'Customer' ),
            'public' => false,  // Private to admin
            'show_ui' => true,
            'supports' => array( 'title', 'editor', 'thumbnail' ), // Title = Name, Editor = Notes
            'menu_icon' => 'dashicons-id',
        ));

        // 2. Project / Job Entity
        register_post_type( 'ap_project', array(
            'labels' => array( 'name' => 'Projects', 'singular_name' => 'Project' ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title', 'editor' ),
            'menu_icon' => 'dashicons-camera',
        ));

        // 3. Invoice Entity
        register_post_type( 'ap_invoice', array(
            'labels' => array( 'name' => 'Invoices', 'singular_name' => 'Invoice' ),
            'public' => true,   // Accessible by client via link
            'show_ui' => true,
            'exclude_from_search' => true,
            'menu_icon' => 'dashicons-media-spreadsheet',
        ));

        // 4. Contract Entity
        register_post_type( 'ap_contract', array(
            'labels' => array( 'name' => 'Contracts', 'singular_name' => 'Contract' ),
            'public' => true,   // Accessible by client for signing
            'show_ui' => true,
            'exclude_from_search' => true,
            'menu_icon' => 'dashicons-welcome-write-blog',
        ));
    }

    // Meta Boxes for "Project Details" and "Customer Info"
    public function add_custom_meta_boxes() {
        add_meta_box( 'ap_project_details', 'Project Logistics', array($this, 'render_project_meta'), 'ap_project', 'normal', 'high' );
        add_meta_box( 'ap_contract_sign', 'Signature Data', array($this, 'render_signature_meta'), 'ap_contract', 'side', 'default' );
    }

    public function render_project_meta( $post ) {
        $stage = get_post_meta( $post->ID, '_ap_project_stage', true );
        $date  = get_post_meta( $post->ID, '_ap_project_date', true );
        ?>
        <p>
            <label>Current Stage:</label>
            <select name="ap_project_stage">
                <option value="lead" <?php selected($stage, 'lead'); ?>>Lead Inquiry</option>
                <option value="proposal" <?php selected($stage, 'proposal'); ?>>Proposal Sent</option>
                <option value="editing" <?php selected($stage, 'editing'); ?>>Editing</option>
                <option value="delivered" <?php selected($stage, 'delivered'); ?>>Delivered</option>
            </select>
        </p>
        <p>
            <label>Shoot Date:</label>
            <input type="date" name="ap_project_date" value="<?php echo esc_attr($date); ?>">
        </p>
        <?php
    }
    // Add this to existing add_custom_meta_boxes()
    add_meta_box( 'ap_invoice_gen', 'Generate Content', array($this, 'render_invoice_gen'), 'ap_invoice', 'side', 'high' );

    public function render_invoice_gen( $post ) {
        $templates = get_posts(array('post_type' => 'ap_template', 'numberposts' => -1));
        ?>
        <p><strong>Apply Template:</strong></p>
        <select id="ap_template_selector">
            <option value="">Select a template...</option>
            <?php foreach($templates as $t): ?>
                <option value="<?php echo $t->ID; ?>"><?php echo esc_html($t->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button" id="ap_load_template">Load</button>
        <p class="description">Warning: Overwrites current editor content.</p>
        
        <script>
        jQuery('#ap_load_template').click(function(){
            var templateId = jQuery('#ap_template_selector').val();
            if(!templateId) return;

            // AJAX call to fetch template content (simplified)
            jQuery.post(ajaxurl, {
                action: 'ap_get_template_content',
                template_id: templateId
            }, function(response){
                if(response.success) {
                    // Assuming Classic Editor (TinyMCE)
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        tinyMCE.get('content').setContent(response.data);
                    } else {
                        jQuery('#content').val(response.data);
                    }
                }
            });
        });
        </script>
        <?php
    }

    public function save_meta_data( $post_id ) {
        if ( isset( $_POST['ap_project_stage'] ) ) {
            $old_stage = get_post_meta( $post_id, '_ap_project_stage', true );
            $new_stage = sanitize_text_field( $_POST['ap_project_stage'] );
            
            update_post_meta( $post_id, '_ap_project_stage', $new_stage );
            update_post_meta( $post_id, '_ap_project_date', sanitize_text_field( $_POST['ap_project_date'] ) );

            // Trigger Automation Hook if stage changed
            if ( $old_stage !== $new_stage ) {
                do_action( 'ap_project_stage_change', $post_id, $new_stage, $old_stage );
            }
        }
    }
}
