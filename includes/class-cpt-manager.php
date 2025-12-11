<?php
/**
 * Manages Custom Post Types and Meta Boxes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_CPT_Manager {

    public function init() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_data' ) );
        add_action( 'save_post', array( $this, 'save_invoice_line_items' ) );
    }

    public function register_post_types() {
        // 1. Customer Entity
        register_post_type( 'ap_customer', array(
            'labels' => array(
                'name' => 'Customers',
                'singular_name' => 'Customer',
                'add_new' => 'Add New Customer',
                'add_new_item' => 'Add New Customer',
                'edit_item' => 'Edit Customer',
                'new_item' => 'New Customer',
                'view_item' => 'View Customer',
                'search_items' => 'Search Customers',
                'not_found' => 'No customers found',
                'not_found_in_trash' => 'No customers found in Trash',
            ),
            'public' => false,  // Private to admin
            'show_ui' => true,
            'show_in_menu' => 'aperture-dashboard',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => array( 'title', 'editor', 'thumbnail' ), // Title = Name, Editor = Notes
            'menu_icon' => 'dashicons-id',
        ));

        // 2. Project / Job Entity
        register_post_type( 'ap_project', array(
            'labels' => array(
                'name' => 'Projects',
                'singular_name' => 'Project',
                'add_new' => 'Add New Project',
                'add_new_item' => 'Add New Project',
                'edit_item' => 'Edit Project',
                'new_item' => 'New Project',
                'view_item' => 'View Project',
                'search_items' => 'Search Projects',
                'not_found' => 'No projects found',
                'not_found_in_trash' => 'No projects found in Trash',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'aperture-dashboard',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => array( 'title', 'editor' ),
            'menu_icon' => 'dashicons-camera',
        ));

        // 3. Invoice Entity
        register_post_type( 'ap_invoice', array(
            'labels' => array(
                'name' => 'Invoices',
                'singular_name' => 'Invoice',
                'add_new' => 'Create New Invoice',
                'add_new_item' => 'Create New Invoice',
                'edit_item' => 'Edit Invoice',
                'new_item' => 'New Invoice',
                'view_item' => 'View Invoice',
                'search_items' => 'Search Invoices',
                'not_found' => 'No invoices found',
                'not_found_in_trash' => 'No invoices found in Trash',
            ),
            'public' => true,   // Accessible by client via link
            'show_ui' => true,
            'show_in_menu' => 'aperture-dashboard',
            'exclude_from_search' => true,
            'menu_icon' => 'dashicons-media-spreadsheet',
            'supports' => array( 'title', 'editor' ), // Kept editor for Terms/Notes
        ));

        // 4. Contract Entity
        register_post_type( 'ap_contract', array(
            'labels' => array(
                'name' => 'Contracts',
                'singular_name' => 'Contract',
                'add_new' => 'Create New Contract',
                'add_new_item' => 'Create New Contract',
                'edit_item' => 'Edit Contract',
                'new_item' => 'New Contract',
                'view_item' => 'View Contract',
                'search_items' => 'Search Contracts',
                'not_found' => 'No contracts found',
                'not_found_in_trash' => 'No contracts found in Trash',
            ),
            'public' => true,   // Accessible by client for signing
            'show_ui' => true,
            'show_in_menu' => 'aperture-dashboard',
            'exclude_from_search' => true,
            'menu_icon' => 'dashicons-welcome-write-blog',
            'supports' => array( 'title', 'editor' ),
        ));
    }

    // --- Meta Box Registrations ---

    public function add_custom_meta_boxes() {
        // Projects
        add_meta_box( 'ap_project_details', 'Project Logistics', array($this, 'render_project_meta'), 'ap_project', 'normal', 'high' );
        
        // Contracts
        add_meta_box( 'ap_contract_sign', 'Signature Data', array($this, 'render_signature_meta'), 'ap_contract', 'side', 'default' );
        
        // Invoices
        add_meta_box( 'ap_invoice_client', 'Client Details', array($this, 'render_client_meta'), 'ap_invoice', 'normal', 'high' );
        add_meta_box( 'ap_invoice_gen', 'Generate Content', array($this, 'render_invoice_gen'), 'ap_invoice', 'side', 'high' );
        add_meta_box( 'ap_invoice_lines', 'Line Items', array( $this, 'render_line_items_box' ), 'ap_invoice', 'normal', 'high' );
    }

    // --- Render Functions ---

    public function render_client_meta( $post ) {
        wp_nonce_field( 'ap_save_meta_data', 'ap_meta_nonce' ); // Security Nonce

        $first_name = get_post_meta( $post->ID, '_ap_client_first_name', true );
        $last_name  = get_post_meta( $post->ID, '_ap_client_last_name', true );
        $email      = get_post_meta( $post->ID, '_ap_client_email', true );
        $address    = get_post_meta( $post->ID, '_ap_client_address', true );
        $due_date   = get_post_meta( $post->ID, '_ap_invoice_due_date', true );
        ?>
        <div class="ap-meta-grid">
            <p>
                <label for="ap_client_first_name">First Name</label><br>
                <input type="text" name="ap_client_first_name" id="ap_client_first_name" value="<?php echo esc_attr($first_name); ?>" class="widefat">
            </p>
            <p>
                <label for="ap_client_last_name">Last Name</label><br>
                <input type="text" name="ap_client_last_name" id="ap_client_last_name" value="<?php echo esc_attr($last_name); ?>" class="widefat">
            </p>
            <p>
                <label for="ap_client_email">Email</label><br>
                <input type="email" name="ap_client_email" id="ap_client_email" value="<?php echo esc_attr($email); ?>" class="widefat">
            </p>
            <p>
                <label for="ap_client_address">Billing Address</label><br>
                <textarea name="ap_client_address" id="ap_client_address" class="widefat" rows="3"><?php echo esc_textarea($address); ?></textarea>
            </p>
            <p>
                <label for="ap_invoice_due_date">Due Date</label><br>
                <input type="date" name="ap_invoice_due_date" id="ap_invoice_due_date" value="<?php echo esc_attr($due_date); ?>" class="widefat">
            </p>
        </div>
        <style>
            .ap-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .ap-meta-grid p { margin: 0; }
        </style>
        <?php
    }

    public function render_project_meta( $post ) {
        wp_nonce_field( 'ap_save_meta_data', 'ap_meta_nonce' ); // Security Nonce

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

    public function render_signature_meta( $post ) {
        $signed = get_post_meta( $post->ID, '_ap_contract_signed', true );
        $sig_img = get_post_meta( $post->ID, '_ap_signature_image', true );
        $date = get_post_meta( $post->ID, '_ap_signed_date', true );
        
        if ( $signed ) {
            echo '<div style="color:green; font-weight:bold;">✅ SIGNED</div>';
            echo '<p>Date: ' . esc_html($date) . '</p>';
            if ( $sig_img ) {
                echo '<p><strong>Signature:</strong><br><img src="' . esc_url($sig_img) . '" style="max-width:100%; border:1px solid #ccc;"></p>';
            }
        } else {
            echo '<div style="color:red;">❌ Awaiting Signature</div>';
        }
    }

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
        <p class="description">Note: Overwrites editor content.</p>
        
        <script>
        jQuery(document).ready(function($){
            $('#ap_load_template').click(function(){
                var templateId = $('#ap_template_selector').val();
                if(!templateId) return;

                // AJAX call to fetch template content
                // Requires 'ap_get_template_content' action handled in Template Manager
                $.post(ajaxurl, {
                    action: 'ap_get_template_content',
                    template_id: templateId
                }, function(response){
                    if(response.success) {
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                            tinyMCE.get('content').setContent(response.data);
                        } else {
                            $('#content').val(response.data);
                        }
                    } else {
                        alert('Error loading template');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function render_line_items_box( $post ) {
        $items = get_post_meta( $post->ID, '_ap_line_items', true );
        if ( ! is_array( $items ) ) $items = array();
        ?>
        <div id="ap-line-items-wrapper">
            <table class="widefat" id="ap-line-items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Rate ($)</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($items)): ?>
                        <tr class="ap-item-row">
                            <td><input type="text" name="ap_item_desc[]" style="width:100%"></td>
                            <td><input type="number" name="ap_item_qty[]" class="qty" value="1" style="width:60px"></td>
                            <td><input type="number" name="ap_item_rate[]" class="rate" step="0.01" style="width:100px"></td>
                            <td><span class="row-total">$0.00</span></td>
                            <td><button type="button" class="button remove-row">x</button></td>
                        </tr>
                    <?php else: foreach($items as $item): ?>
                        <tr class="ap-item-row">
                            <td><input type="text" name="ap_item_desc[]" value="<?php echo esc_attr($item['desc']); ?>" style="width:100%"></td>
                            <td><input type="number" name="ap_item_qty[]" class="qty" value="<?php echo esc_attr($item['qty']); ?>" style="width:60px"></td>
                            <td><input type="number" name="ap_item_rate[]" class="rate" step="0.01" value="<?php echo esc_attr($item['rate']); ?>" style="width:100px"></td>
                            <td><span class="row-total">$<?php echo number_format($item['qty'] * $item['rate'], 2); ?></span></td>
                            <td><button type="button" class="button remove-row">x</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <button type="button" class="button button-primary" id="add-row" style="margin-top:10px;">+ Add Item</button>
            <p style="text-align:right; font-weight:bold; font-size:1.2em;">Grand Total: <span id="grand-total">$0.00</span></p>
        </div>

        <script>
            jQuery(document).ready(function($){
                function calcTotals() {
                    var grand = 0;
                    $('.ap-item-row').each(function(){
                        var qty = parseFloat($(this).find('.qty').val()) || 0;
                        var rate = parseFloat($(this).find('.rate').val()) || 0;
                        var total = qty * rate;
                        $(this).find('.row-total').text('$' + total.toFixed(2));
                        grand += total;
                    });
                    $('#grand-total').text('$' + grand.toFixed(2));
                }

                $('#ap-line-items-table').on('input', 'input', calcTotals);
                
                $('#add-row').click(function(){
                    var row = $('.ap-item-row').first().clone();
                    row.find('input').val(''); 
                    row.find('.qty').val(1);
                    row.find('.row-total').text('$0.00');
                    $('#ap-line-items-table tbody').append(row);
                });

                $('#ap-line-items-table').on('click', '.remove-row', function(){
                    if($('.ap-item-row').length > 1) {
                        $(this).closest('tr').remove();
                        calcTotals();
                    }
                });
                
                calcTotals(); 
            });
        </script>
        <?php
    }

    // --- Save Logic ---

    public function save_meta_data( $post_id ) {
        // 1. Verify Nonce
        if ( ! isset( $_POST['ap_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ap_meta_nonce'], 'ap_save_meta_data' ) ) {
            return;
        }

        // 2. Autosave/Permissions Check
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        
        // Save Project Meta
        if ( isset( $_POST['ap_project_stage'] ) ) {
            $old_stage = get_post_meta( $post_id, '_ap_project_stage', true );
            $new_stage = sanitize_text_field( $_POST['ap_project_stage'] );
            
            update_post_meta( $post_id, '_ap_project_stage', $new_stage );
            update_post_meta( $post_id, '_ap_project_date', sanitize_text_field( $_POST['ap_project_date'] ) );

            if ( $old_stage !== $new_stage ) {
                do_action( 'ap_project_stage_change', $post_id, $new_stage, $old_stage );
            }
        }

        // Save Invoice Client Meta
        if ( isset( $_POST['ap_client_first_name'] ) ) {
            update_post_meta( $post_id, '_ap_client_first_name', sanitize_text_field( $_POST['ap_client_first_name'] ) );
            update_post_meta( $post_id, '_ap_client_last_name', sanitize_text_field( $_POST['ap_client_last_name'] ) );
            update_post_meta( $post_id, '_ap_client_email', sanitize_email( $_POST['ap_client_email'] ) );
            update_post_meta( $post_id, '_ap_client_address', sanitize_textarea_field( $_POST['ap_client_address'] ) );
        }

        if ( isset( $_POST['ap_invoice_due_date'] ) ) {
             update_post_meta( $post_id, '_ap_invoice_due_date', sanitize_text_field( $_POST['ap_invoice_due_date'] ) );
        }
    }

    public function save_invoice_line_items( $post_id ) {
        // 1. Verify Nonce (Re-use the same nonce from the meta box area)
        if ( ! isset( $_POST['ap_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ap_meta_nonce'], 'ap_save_meta_data' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        if ( ! isset( $_POST['ap_item_desc'] ) ) return;

        $descs = $_POST['ap_item_desc'];
        $qtys  = $_POST['ap_item_qty'];
        $rates = $_POST['ap_item_rate'];
        $items = array();
        $grand_total = 0;

        for ( $i = 0; $i < count( $descs ); $i++ ) {
            if ( ! empty( $descs[$i] ) ) {
                $qty = floatval( $qtys[$i] );
                $rate = floatval( $rates[$i] );
                $items[] = array(
                    'desc' => sanitize_text_field( $descs[$i] ),
                    'qty'  => $qty,
                    'rate' => $rate
                );
                $grand_total += ($qty * $rate);
            }
        }

        update_post_meta( $post_id, '_ap_line_items', $items );
        update_post_meta( $post_id, '_ap_invoice_total', $grand_total );
    }
}
