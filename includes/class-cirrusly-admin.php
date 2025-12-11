<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Cirrusly_Admin {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_data' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets( $hook ) {
        global $post;
        if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && 'product' === $post->post_type ) {
            wp_enqueue_media();
            wp_enqueue_script( 'cirrusly-admin-js', CIRRUSLY_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), CIRRUSLY_VERSION, true );
            wp_enqueue_style( 'cirrusly-admin-css', CIRRUSLY_URL . 'assets/css/admin.css', array(), CIRRUSLY_VERSION );
            // WP default datepicker style
            wp_enqueue_style( 'jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css' );
        }
    }

    public function register_meta_box() {
        add_meta_box( 'cw_product_files_box', 'Product Downloads & Attachments', array( $this, 'render_meta_box' ), 'product', 'normal', 'high' );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'cw_save_files_data', 'cw_files_nonce' );

        // Fetch Data
        $names   = get_post_meta( $post->ID, 'wcpoa_attachment_name', true ) ?: [];
        $urls    = get_post_meta( $post->ID, 'wcpoa_attachment_url', true ) ?: [];
        $vis     = get_post_meta( $post->ID, 'cw_file_visibility', true ) ?: [];
        $status  = get_post_meta( $post->ID, 'cw_file_unlock_status', true ) ?: [];
        $roles   = get_post_meta( $post->ID, 'cw_file_role_restrict', true ) ?: [];
        $expiry  = get_post_meta( $post->ID, 'cw_file_expiry', true ) ?: [];
        
        // Global Placement Setting for this product
        $placement = get_post_meta( $post->ID, 'cw_attachments_placement', true ) ?: 'description';
        $tab_title = get_post_meta( $post->ID, 'cw_attachments_tab_title', true ) ?: 'Downloads';

        ?>
        <div class="cw-meta-header">
            <label><strong>Display Position:</strong>
                <select name="cw_attachments_placement">
                    <option value="description" <?php selected($placement, 'description'); ?>>Bottom of Description</option>
                    <option value="tab" <?php selected($placement, 'tab'); ?>>New Product Tab</option>
                    <option value="after_cart" <?php selected($placement, 'after_cart'); ?>>After Add To Cart Button</option>
                </select>
            </label>
            <label style="margin-left: 15px;"><strong>Tab Title:</strong>
                <input type="text" name="cw_attachments_tab_title" value="<?php echo esc_attr($tab_title); ?>" placeholder="Downloads">
            </label>
        </div>

        <table id="cw-files-table">
            <thead>
                <tr>
                    <th style="width:20px;"></th>
                    <th>File Label</th>
                    <th>File URL / ID</th>
                    <th>Visibility</th>
                    <th>Permissions</th>
                    <th>Order Status</th>
                    <th>Expiry</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="cw-files-tbody">
                <?php if ( ! empty( $names ) ) : foreach ( $names as $i => $name ) : 
                    $v = $vis[$i] ?? 'visible';
                    $s = $status[$i] ?? 'any';
                    $r = $roles[$i] ?? 'all';
                    $e = $expiry[$i] ?? '';
                ?>
                <tr class="cw-file-row">
                    <td><span class="dashicons dashicons-menu cw-row-handle"></span></td>
                    <td><input type="text" name="cw_custom_name[]" value="<?php echo esc_attr( $name ); ?>" class="cw-file-input" placeholder="Name" /></td>
                    <td><input type="text" name="cw_custom_url[]" value="<?php echo esc_attr( $urls[$i] ?? '' ); ?>" class="cw-file-url cw-file-input" placeholder="URL or ID" /></td>
                    
                    <td>
                        <select name="cw_file_vis[]">
                            <option value="visible" <?php selected($v, 'visible'); ?>>Visible</option>
                            <option value="hidden" <?php selected($v, 'hidden'); ?>>Hidden (Order Only)</option>
                        </select>
                    </td>

                    <td>
                        <select name="cw_file_role_restrict[]" style="width: 100px;">
                            <option value="all" <?php selected($r, 'all'); ?>>Everyone</option>
                            <option value="logged_in" <?php selected($r, 'logged_in'); ?>>Logged In</option>
                            <option value="guest" <?php selected($r, 'guest'); ?>>Guests</option>
                            <option value="customer" <?php selected($r, 'customer'); ?>>Customer Role</option>
                            <option value="administrator" <?php selected($r, 'administrator'); ?>>Admin</option>
                        </select>
                    </td>

                    <td>
                        <select name="cw_file_status[]" style="width: 100px;">
                            <option value="any" <?php selected($s, 'any'); ?>>Immediate</option>
                            <option value="processing" <?php selected($s, 'processing'); ?>>Processing</option>
                            <option value="completed" <?php selected($s, 'completed'); ?>>Completed</option>
                        </select>
                    </td>

                    <td>
                        <input type="text" name="cw_file_expiry[]" value="<?php echo esc_attr($e); ?>" class="cw-datepicker cw-file-input" placeholder="YYYY-MM-DD" style="width: 90px;" />
                    </td>

                    <td>
                        <button type="button" class="button cw-upload-btn" title="Upload"><span class="dashicons dashicons-upload"></span></button>
                        <button type="button" class="button cw-remove-row" title="Delete"><span class="dashicons dashicons-trash"></span></button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <button type="button" class="button button-primary" id="cw-add-row">Add Resource</button>
        <?php
    }

    public function save_data( $post_id ) {
        if ( ! isset( $_POST['cw_files_nonce'] ) || ! wp_verify_nonce( $_POST['cw_files_nonce'], 'cw_save_files_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Save Global Settings
        if(isset($_POST['cw_attachments_placement'])) update_post_meta($post_id, 'cw_attachments_placement', sanitize_text_field($_POST['cw_attachments_placement']));
        if(isset($_POST['cw_attachments_tab_title'])) update_post_meta($post_id, 'cw_attachments_tab_title', sanitize_text_field($_POST['cw_attachments_tab_title']));

        $names = $_POST['cw_custom_name'] ?? [];
        $urls  = $_POST['cw_custom_url'] ?? [];
        $vis   = $_POST['cw_file_vis'] ?? [];
        $status= $_POST['cw_file_status'] ?? [];
        $roles = $_POST['cw_file_role_restrict'] ?? [];
        $expiry= $_POST['cw_file_expiry'] ?? [];

        $clean_names = []; $clean_urls = []; $clean_vis = []; $clean_status = []; $clean_roles = []; $clean_expiry = [];

        for ( $i = 0; $i < count( $names ); $i++ ) {
            if ( ! empty( $names[$i] ) && ! empty( $urls[$i] ) ) {
                $clean_names[]  = sanitize_text_field( $names[$i] );
                $clean_urls[]   = sanitize_text_field( $urls[$i] );
                $clean_vis[]    = sanitize_text_field( $vis[$i] ?? 'visible' );
                $clean_status[] = sanitize_text_field( $status[$i] ?? 'any' );
                $clean_roles[]  = sanitize_text_field( $roles[$i] ?? 'all' );
                $clean_expiry[] = sanitize_text_field( $expiry[$i] ?? '' );
            }
        }

        update_post_meta( $post_id, 'wcpoa_attachment_name', $clean_names );
        update_post_meta( $post_id, 'wcpoa_attachment_url', $clean_urls );
        update_post_meta( $post_id, 'cw_file_visibility', $clean_vis );
        update_post_meta( $post_id, 'cw_file_unlock_status', $clean_status );
        update_post_meta( $post_id, 'cw_file_role_restrict', $clean_roles );
        update_post_meta( $post_id, 'cw_file_expiry', $clean_expiry );
    }
}