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

        // 4. Register Project Board Page
        add_action( 'admin_menu', array( $this, 'register_board_page' ) );
    }

    public function register_board_page() {
        add_submenu_page(
            'aperture-dashboard',
            'Project Board',
            'Project Board',
            'manage_options',
            'ap-project-board',
            array( $this, 'render_project_board' )
        );
    }

    public function render_project_board() {
        $stages = array(
            'lead' => 'Leads',
            'proposal' => 'Proposal Sent',
            'editing' => 'Editing',
            'delivered' => 'Delivered'
        );

        $projects = get_posts(array(
            'post_type' => 'ap_project',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $grouped = array('lead'=>[], 'proposal'=>[], 'editing'=>[], 'delivered'=>[]);
        foreach($projects as $p) {
            $s = get_post_meta($p->ID, '_ap_project_stage', true);
            if(isset($grouped[$s])) $grouped[$s][] = $p;
            else $grouped['lead'][] = $p; // Fallback
        }

        echo '<div class="wrap"><h1>Project Board</h1><div class="ap-kanban-board">';

        foreach($stages as $key => $label) {
            echo '<div class="ap-kanban-col">';
            echo '<h3>' . esc_html($label) . ' <span class="count">' . count($grouped[$key]) . '</span></h3>';
            echo '<div class="ap-kanban-list">';

            if(!empty($grouped[$key])) {
                foreach($grouped[$key] as $post) {
                    $customer_id = get_post_meta($post->ID, '_ap_project_customer', true);
                    $date = get_post_meta($post->ID, '_ap_project_date', true);
                    echo '<div class="ap-kanban-card">';
                    echo '<h4><a href="' . get_edit_post_link($post->ID) . '">' . esc_html($post->post_title) . '</a></h4>';
                    if($customer_id) echo '<p class="customer">' . get_the_title($customer_id) . '</p>';
                    if($date) echo '<p class="date">📅 ' . esc_html($date) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p class="empty-col">No projects</p>';
            }

            echo '</div></div>';
        }

        echo '</div></div>';

        ?>
        <style>
            .ap-kanban-board { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; }
            .ap-kanban-col { flex: 1; min-width: 250px; background: #f0f0f1; padding: 10px; border-radius: 4px; }
            .ap-kanban-col h3 { text-align: center; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-top: 5px; }
            .ap-kanban-col h3 .count { background: #ccc; border-radius: 50%; padding: 2px 8px; font-size: 0.8em; margin-left: 5px; }
            .ap-kanban-list { min-height: 200px; }
            .ap-kanban-card { background: white; padding: 15px; margin-bottom: 10px; border-radius: 3px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); border-left: 4px solid #0073aa; }
            .ap-kanban-card h4 { margin: 0 0 5px 0; font-size: 1.1em; }
            .ap-kanban-card p { margin: 0; color: #666; font-size: 0.9em; }
            .ap-kanban-card .customer { font-weight: bold; margin-bottom: 5px; }
            .empty-col { text-align: center; color: #999; font-style: italic; margin-top: 20px; }
        </style>
        <?php
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
        
        $total_spent = 0; // Placeholder
        
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
