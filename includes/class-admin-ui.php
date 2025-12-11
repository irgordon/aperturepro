<?php
/**
 * Enhances the WordPress Admin UI with CRM features.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Admin_UI {

    public function init() {
        // 1. Add Custom Columns to Project List
        add_filter( 'manage_ap_project_posts_columns', array( $this, 'add_project_columns' ) );
        add_action( 'manage_ap_project_posts_custom_column', array( $this, 'render_project_columns' ), 10, 2 );
        
        // 2. Add Sortable Columns
        add_filter( 'manage_edit-ap_project_sortable_columns', array( $this, 'sortable_project_columns' ) );

        // 3. Add "360 View" Meta Box to Customer Screen
        add_action( 'add_meta_boxes', array( $this, 'add_customer_overview' ) );
    }

    /**
     * --- Project List Columns ---
     */
    public function add_project_columns( $columns ) {
        $new = array();
        $new['cb'] = $columns['cb'];
        $new['title'] = 'Project Name';
        $new['ap_stage'] = 'Stage';
        $new['ap_customer'] = 'Customer';
        $new['ap_date'] = 'Shoot Date';
        $new['ap_tasks'] = 'Open Tasks';
        $new['date'] = $columns['date'];
        return $new;
    }

    public function render_project_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'ap_stage':
                $stage = get_post_meta( $post_id, '_ap_project_stage', true );
                // Color coding stages
                $colors = array(
                    'lead'     => '#e5e5e5', // Grey
                    'proposal' => '#ffefc1', // Yellow
                    'editing'  => '#c1e1ff', // Blue
                    'delivered'=> '#d4edda'  // Green
                );
                $bg = isset($colors[$stage]) ? $colors[$stage] : '#fff';
                // Fallback text if stage is empty
                $stage_text = $stage ? ucfirst($stage) : 'Unknown';
                
                echo '<span style="background:'.$bg.'; padding: 5px 10px; border-radius: 4px; font-weight:bold;">' . esc_html($stage_text) . '</span>';
                break;

            case 'ap_customer':
                $cust_id = get_post_meta( $post_id, '_ap_project_customer', true );
                if ( $cust_id ) {
                    echo '<a href="' . get_edit_post_link($cust_id) . '"><strong>' . esc_html(get_the_title($cust_id)) . '</strong></a>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
                break;

            case 'ap_date':
                echo esc_html( get_post_meta( $post_id, '_ap_project_date', true ) );
                break;
                
            case 'ap_tasks':
                // Count tasks that are NOT 'done'
                $args = array(
                    'post_type' => 'ap_task',
                    'meta_query' => array(
                        array('key' => '_ap_task_project_id', 'value' => $post_id),
                        array('key' => '_ap_task_status', 'value' => 'done', 'compare' => '!=')
                    )
                );
                $count = count( get_posts($args) );
                if($count > 0) echo '<span style="color:red; font-weight:bold;">' . $count . '</span>';
                else echo '<span style="color:green;">✔</span>';
                break;
        }
    }
    
    public function sortable_project_columns( $columns ) {
        $columns['ap_date'] = 'ap_date';
        return $columns;
    }

    /**
     * --- Customer 360 View ---
     */
    public function add_customer_overview() {
        add_meta_box( 
            'ap_customer_360', 
            'Customer Relationship Overview', 
            array( $this, 'render_customer_360' ), 
            'ap_customer', 
            'normal', 
            'high' 
        );
    }

    public function render_customer_360( $post ) {
        // 1. Fetch Projects for this Customer
        $projects = get_posts(array(
            'post_type' => 'ap_project',
            'meta_key' => '_ap_project_customer',
            'meta_value' => $post->ID,
            'numberposts' => -1
        ));
        
        // 2. Fetch/Calculate Financials (Placeholder logic)
        // In a real scenario, you'd loop through linked invoices and sum 'total' where status='paid'
        $total_spent = 0; 
        
        ?>
        <div class="ap-360-dashboard" style="display:flex; gap: 20px;">
            <div class="ap-card" style="flex:1; background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px;">
                <h3>Projects</h3>
                <?php if($projects): ?>
                    <ul style="list-style:none; padding:0; margin:0;">
                    <?php foreach($projects as $p): 
                        $stage = get_post_meta($p->ID, '_ap_project_stage', true);
                        ?>
                        <li style="margin-bottom:8px; border-bottom:1px solid #eee; padding-bottom:8px;">
                            <a href="<?php echo get_edit_post_link($p->ID); ?>"><strong><?php echo esc_html($p->post_title); ?></strong></a><br>
                            <small>Stage: <?php echo ucfirst($stage); ?></small>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No projects found.</p>
                <?php endif; ?>
                <a href="<?php echo admin_url('post-new.php?post_type=ap_project'); ?>" class="button button-small" style="margin-top:10px;">New Project</a>
            </div>

            <div class="ap-card" style="flex:1; background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px;">
                <h3>Financials</h3>
                <p style="font-size: 2em; margin: 10px 0;">$<?php echo number_format($total_spent, 2); ?></p>
                <p style="color:#666;">Lifetime Value</p>
                <a href="<?php echo admin_url('post-new.php?post_type=ap_invoice'); ?>" class="button button-small">Create Invoice</a>
            </div>
            
            <div class="ap-card" style="flex:1; background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px;">
                <h3>Contact Info</h3>
                <?php 
                $email = get_post_meta($post->ID, '_ap_client_email', true);
                $phone = get_post_meta($post->ID, '_ap_client_phone', true);
                ?>
                <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
                <p><strong>Phone:</strong> <?php echo esc_html($phone ? $phone : 'N/A'); ?></p>
            </div>
        </div>
        <?php
    }
}
