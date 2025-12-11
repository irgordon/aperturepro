<?php
/**
 * The Main Command Center Dashboard for AperturePro.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Dashboard_Page {

    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    public function register_menu() {
        add_menu_page(
            'AperturePro',
            'AperturePro',
            'manage_options',
            'aperture-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-camera',
            2
        );
    }

    public function render_dashboard() {
        // 1. Gather Metrics
        $leads_count = wp_count_posts( 'ap_project' )->publish;
        
        global $wpdb;
        // Revenue
        $revenue = $wpdb->get_var( "
            SELECT SUM(meta_value) 
            FROM $wpdb->postmeta pm
            JOIN $wpdb->posts p ON p.ID = pm.post_id
            WHERE p.post_type = 'ap_invoice' 
            AND pm.meta_key = '_ap_invoice_total'
            AND p.ID IN (
                SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ap_invoice_status' AND meta_value = 'paid'
            )
        " );

        // Pending Invoices Count
        // Simplified: Count all invoices that are NOT paid
        // Note: A more robust query would check meta key existence or specific 'pending' value.
        // Assuming status key is '_ap_invoice_status'
        $pending_count = 0;
        $invoices = get_posts(array('post_type' => 'ap_invoice', 'numberposts' => -1));
        foreach($invoices as $inv) {
            $status = get_post_meta($inv->ID, '_ap_invoice_status', true);
            if($status !== 'paid') {
                $pending_count++;
            }
        }

        // Upcoming Tasks (High Priority)
        $tasks = get_posts(array(
            'post_type' => 'ap_task',
            'meta_key' => '_ap_task_priority',
            'meta_value' => 'high',
            'posts_per_page' => 5,
            'meta_query' => array(
                array( 'key' => '_ap_task_status', 'compare' => 'NOT EXISTS' )
            )
        ));

        ?>
        <div class="wrap">
            <h1>AperturePro Command Center</h1>
            
            <div class="ap-dash-grid">
                <div class="ap-metric-card">
                    <h3>Total Revenue</h3>
                    <div class="ap-metric-val">$<?php echo number_format((float)$revenue, 2); ?></div>
                </div>
                <div class="ap-metric-card">
                    <h3>Active Projects</h3>
                    <div class="ap-metric-val"><?php echo intval($leads_count); ?></div>
                </div>
                <div class="ap-metric-card">
                    <h3>Pending Invoices</h3>
                    <div class="ap-metric-val"><?php echo intval($pending_count); ?></div>
                </div>

                <div class="ap-dash-panel" style="grid-column: span 2;">
                    <h2>High Priority Tasks</h2>
                    <?php if($tasks): ?>
                        <ul class="ap-task-list">
                        <?php foreach($tasks as $t): 
                            $project_id = get_post_meta($t->ID, '_ap_task_project_id', true);
                        ?>
                            <li>
                                <span class="dashicons dashicons-marker"></span>
                                <strong><?php echo esc_html($t->post_title); ?></strong>
                                <span class="meta">for <?php echo get_the_title($project_id); ?></span>
                                <a href="<?php echo get_edit_post_link($t->ID); ?>" class="button button-small">View</a>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No high priority tasks. Great job!</p>
                    <?php endif; ?>
                </div>

                <div class="ap-dash-panel">
                    <h2>Quick Actions</h2>
                    <ul class="ap-actions-list">
                        <li><a href="<?php echo admin_url('post-new.php?post_type=ap_project'); ?>" class="button">Create New Project</a></li>
                        <li><a href="<?php echo admin_url('post-new.php?post_type=ap_invoice'); ?>" class="button">Create New Invoice</a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=ap-project-board'); ?>" class="button button-primary">Project Board</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <style>
            .ap-dash-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
            @media (max-width: 768px) {
                .ap-dash-grid { grid-template-columns: 1fr; }
                .ap-dash-panel { grid-column: auto !important; }
            }
            .ap-metric-card { background: white; padding: 25px; border-left: 5px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .ap-metric-card h3 { margin: 0 0 10px 0; color: #666; font-size: 0.9em; text-transform: uppercase; }
            .ap-metric-val { font-size: 2.5em; font-weight: bold; color: #333; }
            .ap-dash-panel { background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .ap-task-list { list-style: none; padding: 0; margin: 0; }
            .ap-task-list li { border-bottom: 1px solid #eee; padding: 10px 0; display: flex; align-items: center; justify-content: space-between; }
            .ap-task-list .meta { color: #999; font-size: 0.9em; margin-left: 10px; flex-grow: 1; }
            .ap-actions-list li { margin-bottom: 10px; }
            .ap-actions-list .button { width: 100%; text-align: center; }
        </style>
        <?php
    }
}
