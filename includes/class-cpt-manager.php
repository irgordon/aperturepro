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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function enqueue_admin_scripts( $hook ) {
        global $post;
        if ( ! $post || 'ap_invoice' !== $post->post_type ) {
            return;
        }

        wp_enqueue_style( 'ap-invoice-editor', APERTURE_URL . 'assets/css/invoice-editor.css', array(), '1.0' );
        wp_enqueue_script( 'ap-invoice-editor', APERTURE_URL . 'assets/js/invoice-editor.js', array( 'jquery' ), '1.0', true );
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

        // 5. Gallery Entity
        register_post_type( 'ap_gallery', array(
            'labels' => array(
                'name' => 'Galleries',
                'singular_name' => 'Gallery',
                'add_new' => 'Create New Gallery',
                'add_new_item' => 'Create New Gallery',
                'edit_item' => 'Edit Gallery',
                'new_item' => 'New Gallery',
                'view_item' => 'View Gallery',
                'search_items' => 'Search Galleries',
                'not_found' => 'No galleries found',
                'not_found_in_trash' => 'No galleries found in Trash',
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'aperture-dashboard',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'menu_icon' => 'dashicons-format-gallery',
        ));
    }

    // --- Meta Box Registrations ---

    public function add_custom_meta_boxes() {
        // Projects
        add_meta_box( 'ap_project_details', 'Project Logistics', array($this, 'render_project_meta'), 'ap_project', 'normal', 'high' );
        
        // Contracts
        add_meta_box( 'ap_contract_sign', 'Signature Data', array($this, 'render_signature_meta'), 'ap_contract', 'side', 'default' );
        
        // Invoices - New Single Main Editor
        add_meta_box(
            'ap_invoice_main_editor',
            'Invoice Editor',
            array($this, 'render_invoice_editor'),
            'ap_invoice',
            'normal',
            'high'
        );
    }

    // --- Render Functions ---

    public function render_invoice_editor( $post ) {
        wp_nonce_field( 'ap_save_meta_data', 'ap_meta_nonce' ); // Security Nonce

        // Retrieve Data
        $first_name = get_post_meta( $post->ID, '_ap_client_first_name', true );
        $last_name  = get_post_meta( $post->ID, '_ap_client_last_name', true );
        $email      = get_post_meta( $post->ID, '_ap_client_email', true );
        $address    = get_post_meta( $post->ID, '_ap_client_address', true );
        $due_date   = get_post_meta( $post->ID, '_ap_invoice_due_date', true );
        $items      = get_post_meta( $post->ID, '_ap_line_items', true );
        if ( ! is_array( $items ) ) $items = array();

        // Pass Items to JS
        ?>
        <script>
            var ap_invoice_data = {
                items: <?php echo json_encode( $items ); ?>
            };
        </script>

        <div class="ap-invoice-editor-wrapper">

            <!-- LEFT COLUMN -->
            <div class="ap-inv-main">

                <!-- Customer Section -->
                <div class="ap-inv-card">
                    <h3><span class="dashicons dashicons-admin-users icon"></span> Customer</h3>
                    <div class="ap-inv-input-group">
                        <label>Customer Name</label>
                        <div style="display:flex; gap:10px;">
                             <input type="text" name="ap_client_first_name" placeholder="First Name" value="<?php echo esc_attr($first_name); ?>" class="ap-inv-control">
                             <input type="text" name="ap_client_last_name" placeholder="Last Name" value="<?php echo esc_attr($last_name); ?>" class="ap-inv-control">
                        </div>
                    </div>
                    <div class="ap-inv-input-group">
                        <label>Email</label>
                        <input type="email" name="ap_client_email" placeholder="customer@example.com" value="<?php echo esc_attr($email); ?>" class="ap-inv-control">
                    </div>

                    <!-- Toggle for Address -->
                    <div class="ap-toggle-row">
                        <label><input type="checkbox" id="ap_toggle_address" <?php checked(!empty($address)); ?>> Ship items to / Add address</label>
                    </div>
                    <div id="ap_address_box" class="<?php echo empty($address) ? 'hidden' : ''; ?>" style="margin-top:15px;">
                        <textarea name="ap_client_address" class="ap-inv-control" placeholder="Billing Address"><?php echo esc_textarea($address); ?></textarea>
                    </div>
                    <script>
                        jQuery('#ap_toggle_address').change(function(){
                            jQuery('#ap_address_box').toggleClass('hidden', !this.checked);
                        });
                    </script>
                </div>

                <!-- Items Section -->
                <div class="ap-inv-card">
                    <h3><span class="dashicons dashicons-cart icon"></span> Items <span style="margin-left:auto; font-size:0.8em; font-weight:normal;">USD</span></h3>

                    <!-- Items List Container -->
                    <div id="ap_items_list_visual" class="ap-inv-items-list">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Add Item Form -->
                    <div class="ap-inv-add-form">
                        <div class="ap-inv-item-type-selector">
                            <label class="ap-inv-radio-label"><input type="radio" name="ap_item_type_selector" value="amount"> Amount only</label>
                            <label class="ap-inv-radio-label"><input type="radio" name="ap_item_type_selector" value="quantity" checked> Quantity</label>
                            <label class="ap-inv-radio-label"><input type="radio" name="ap_item_type_selector" value="hours"> Hours</label>
                        </div>

                        <div class="ap-inv-row-inputs">
                            <input type="text" id="ap_new_item_name" class="ap-inv-control" placeholder="Item Name">
                            <div style="position:relative;">
                                <label style="position:absolute; top:-25px; left:0; font-size:12px;">Qty</label>
                                <input type="number" id="ap_new_item_qty" class="ap-inv-control" value="1" step="0.5">
                            </div>
                            <input type="number" id="ap_new_item_price" class="ap-inv-control" placeholder="Price" step="0.01">
                        </div>

                        <div class="ap-inv-meta-links">
                            <a href="#">+ Show tax, discount, date</a>
                        </div>

                        <textarea id="ap_new_item_desc" class="ap-inv-control" placeholder="Description (Optional)"></textarea>

                        <div style="margin-top:10px;">
                            <label><input type="checkbox"> Save item for future invoices</label>
                        </div>

                        <div class="ap-inv-add-btn-row">
                            <button type="button" id="ap_add_item_btn" class="btn-black-pill">Add</button>
                        </div>
                    </div>
                </div>

                <!-- Notes Section (Replaces Editor) -->
                <div class="ap-inv-card">
                    <h3><span class="dashicons dashicons-media-text icon"></span> Notes</h3>
                    <div style="font-size: 0.9em; color:#666; margin-bottom:10px;">Standard Editor below can also be used for Terms.</div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="ap-inv-sidebar">

                <!-- Summary Card -->
                <div class="ap-inv-card">
                    <h3>Invoice <?php echo $post->ID; ?> <a href="#" class="ap-edit-link"><span class="dashicons dashicons-edit"></span> Edit</a></h3>
                    <div class="ap-summary-row">
                        <span>Issued</span>
                        <span><?php echo get_the_date(); ?></span>
                    </div>
                    <div class="ap-summary-row">
                        <span>Due</span>
                        <input type="date" name="ap_invoice_due_date" value="<?php echo esc_attr($due_date); ?>" style="border:none; background:transparent; text-align:right;">
                    </div>
                    <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">
                    <div class="ap-summary-row">
                        <span>Subtotal</span>
                        <span id="ap_summary_subtotal">$0.00</span>
                    </div>
                    <div class="ap-summary-row total">
                        <span>Total (Tax exclusive)</span>
                        <span id="ap_summary_total">$0.00</span>
                    </div>
                </div>

                <!-- Payment Options -->
                <div class="ap-inv-card">
                    <h3>Payment options</h3>
                    <div class="ap-option-row">
                        <label><input type="checkbox" name="ap_allow_partial"> Allow partial payment <span class="dashicons dashicons-editor-help" style="font-size:14px;"></span></label>
                    </div>
                    <div class="ap-option-row">
                        <label><input type="checkbox" name="ap_allow_tip"> Allow tip</label>
                    </div>

                    <h4 style="margin-top:20px; font-size:0.9em;">Available payment methods</h4>
                    <p style="font-size:0.8em; color:#777;">Your customers can choose how to pay.</p>
                    <div class="ap-payment-icons">
                         <span class="ap-pay-icon">Visa</span>
                         <span class="ap-pay-icon">MC</span>
                         <span class="ap-pay-icon">Amex</span>
                         <span class="ap-pay-icon">PayP</span>
                    </div>
                </div>

            </div>

        </div>
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
