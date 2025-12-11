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
            'supports' => array( 'title', 'editor' ), // Title = Invoice ID/Ref
        ));

        // 4. Contract Entity
        register_post_type( 'ap_contract', array(
            'labels' => array( 'name' => 'Contracts', 'singular_name' => 'Contract' ),
            'public' => true,   // Accessible by client for signing
            'show_ui' => true,
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
        add_meta_box( 'ap_invoice_gen', 'Generate Content', array($this, 'render_invoice_gen'), 'ap_invoice', 'side', 'high' );
        add_meta_box( 'ap_invoice_lines', 'Line Items', array( $this, 'render_line_items_box' ), 'ap_invoice', 'normal', 'high' );
    }

    // --- Render Functions ---

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
        <p class="description">Warning: Overwrites current editor content.</p>
        
        <script>
        jQuery('#ap_load_template').click(function(){
            var templateId = jQuery('#ap_template_selector').val();
            if(!templateId) return;

            // AJAX call to fetch template content (Requires handler in Template Manager)
            jQuery.post(ajaxurl, {
                action: 'ap_get_template_content',
                template_id: templateId
            }, function(response){
                if(response.success) {
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
        // Security check usually goes here (nonce)
        
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

    public function save_invoice_line_items( $post_id ) {
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
        update_post_meta( $post_id, '_ap_invoice_total', $grand_total ); // Used by Stripe
    }
}
