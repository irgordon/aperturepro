<?php
/**
 * Handles Project Tasks and Subtasks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Aperture_Task_Manager {

    public function init() {
        add_action( 'init', array( $this, 'register_task_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_task_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_task_meta' ) );
        
        // AJAX hook for "Quick Complete" on dashboard
        add_action( 'wp_ajax_ap_mark_task_complete', array( $this, 'ajax_mark_complete' ) );
    }

    public function register_task_cpt() {
        register_post_type( 'ap_task', array(
            'labels' => array( 'name' => 'Tasks', 'singular_name' => 'Task' ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=ap_project', // Nestle under Projects menu
            'supports' => array( 'title', 'editor' ),
            'hierarchical' => true, // Allows Parent/Child tasks
        ));
    }

    public function add_task_meta_boxes() {
        add_meta_box( 'ap_task_details', 'Task Details', array( $this, 'render_task_meta' ), 'ap_task', 'side', 'default' );
    }

    public function render_task_meta( $post ) {
        $parent_project = get_post_meta( $post->ID, '_ap_task_project_id', true );
        $priority = get_post_meta( $post->ID, '_ap_task_priority', true );
        $due_date = get_post_meta( $post->ID, '_ap_task_due_date', true );
        
        // Get all projects for the dropdown
        $projects = get_posts(array('post_type' => 'ap_project', 'numberposts' => -1));
        ?>
        <p>
            <label><strong>Linked Project:</strong></label><br>
            <select name="ap_task_project_id" style="width:100%">
                <option value="">-- Select Project --</option>
                <?php foreach($projects as $p): ?>
                    <option value="<?php echo $p->ID; ?>" <?php selected($parent_project, $p->ID); ?>>
                        <?php echo esc_html($p->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong>Priority:</strong></label><br>
            <select name="ap_task_priority" style="width:100%">
                <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
                <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
                <option value="high" <?php selected($priority, 'high'); ?>>High</option>
            </select>
        </p>
        <p>
            <label><strong>Due Date:</strong></label><br>
            <input type="date" name="ap_task_due_date" value="<?php echo esc_attr($due_date); ?>" style="width:100%">
        </p>
        <?php
    }

    public function save_task_meta( $post_id ) {
        if ( isset( $_POST['ap_task_project_id'] ) ) {
            update_post_meta( $post_id, '_ap_task_project_id', sanitize_text_field( $_POST['ap_task_project_id'] ) );
            update_post_meta( $post_id, '_ap_task_priority', sanitize_text_field( $_POST['ap_task_priority'] ) );
            update_post_meta( $post_id, '_ap_task_due_date', sanitize_text_field( $_POST['ap_task_due_date'] ) );
        }
    }

    /**
     * AJAX Handler to mark task as done from Dashboard
     */
    public function ajax_mark_complete() {
        // Security check would go here (nonce verification)
        $task_id = intval( $_POST['task_id'] );
        $new_status = sanitize_text_field( $_POST['status'] ); // 'done' or 'todo'
        
        update_post_meta( $task_id, '_ap_task_status', $new_status );
        wp_send_json_success();
    }
}
