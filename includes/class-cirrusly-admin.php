<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Cirrusly_Admin {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_data' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // Register the new menu item
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
    }

    public function enqueue_assets( $hook ) {
        global $post;
        if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && 'product' === $post->post_type ) {
            wp_enqueue_media();
            wp_enqueue_script( 'cirrusly-admin-js', CIRRUSLY_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), CIRRUSLY_VERSION, true );
            wp_enqueue_style( 'cirrusly-admin-css', CIRRUSLY_URL . 'assets/css/admin.css', array(), CIRRUSLY_VERSION );
            wp_enqueue_style( 'jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css' );
        }
    }

    // --- New Menu Logic ---
    public function register_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Product Attachments',
            'Product Attachments',
            'manage_woocommerce',
            'cirrusly-attachments',
            array( $this, 'render_dashboard' )
        );
    }

    public function render_dashboard() {
        // Fetch all products with attachments
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_key'       => 'wcpoa_attachment_name', // Only get products with this key
        );
        $products = get_posts( $args );

        $total_files = 0;
        $total_downloads = 0;
        $rows = [];

        foreach ( $products as $prod ) {
            $pid = $prod->ID;
            $names   = get_post_meta( $pid, 'wcpoa_attachment_name', true ) ?: [];
            $urls    = get_post_meta( $pid, 'wcpoa_attachment_url', true ) ?: [];
            $vis     = get_post_meta( $pid, 'cw_file_visibility', true ) ?: [];
            $roles   = get_post_meta( $pid, 'cw_file_role_restrict', true ) ?: [];
            $expiry  = get_post_meta( $pid, 'cw_file_expiry', true ) ?: [];
            $downloads = get_post_meta( $pid, 'cw_file_downloads', true ) ?: [];

            if ( ! empty( $names ) && is_array( $names ) ) {
                foreach ( $names as $i => $name ) {
                    if ( empty( $name ) ) continue;
                    
                    $d_count = isset($downloads[$i]) ? intval($downloads[$i]) : 0;
                    $total_files++;
                    $total_downloads += $d_count;

                    $file_url = is_numeric( $urls[$i] ) ? wp_get_attachment_url( $urls[$i] ) : $urls[$i];

                    $rows[] = array(
                        'product_id'   => $pid,
                        'product_name' => $prod->post_title,
                        'file_name'    => $name,
                        'file_url'     => $file_url,
                        'visibility'   => $vis[$i] ?? 'visible',
                        'role'         => $roles[$i] ?? 'all',
                        'expiry'       => $expiry[$i] ?? '-',
                        'downloads'    => $d_count
                    );
                }
            }
        }
        ?>
        <div class="wrap">
            <h1>Product Attachments Dashboard</h1>
            <p>Manage and audit all your downloadable resources from one location.</p>
            
            <div style="background:#fff; border:1px solid #ccd0d4; border-left:4px solid #007cba; padding:15px; margin:20px 0; display:flex; gap:30px; box-shadow:0 1px 1px rgba(0,0,0,0.04);">
                <div>
                    <strong style="display:block; font-size:12px; color:#50575e; text-transform:uppercase;">Total Active Files</strong>
                    <span style="font-size:24px; font-weight:bold; color:#1d2327;"><?php echo $total_files; ?></span>
                </div>
                <div>
                    <strong style="display:block; font-size:12px; color:#50575e; text-transform:uppercase;">Total Downloads</strong>
                    <span style="font-size:24px; font-weight:bold; color:#1d2327;"><?php echo $total_downloads; ?></span>
                </div>
            </div>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>File Name</th>
                        <th>Visibility</th>
                        <th>Restrictions</th>
                        <th>Expiry</th>
                        <th>Downloads</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="7">No attachments found. Go edit a product to add some!</td></tr>
                    <?php else : foreach ( $rows as $row ) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link( $row['product_id'] ); ?>"><strong><?php echo esc_html( $row['product_name'] ); ?></strong></a>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $row['file_url'] ); ?>" target="_blank" title="View File">
                                    <span class="dashicons dashicons-media-default" style="font-size:16px; width:16px; height:16px; vertical-align:middle; margin-right:5px;"></span>
                                    <?php echo esc_html( $row['file_name'] ); ?>
                                </a>
                            </td>
                            <td>
                                <?php if($row['visibility'] === 'hidden'): ?>
                                    <span class="badge" style="background:#f0f0f1; color:#50575e; padding:2px 6px; border-radius:3px; font-size:11px;">Hidden</span>
                                <?php else: ?>
                                    <span style="color:#00a32a;">Visible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if($row['role'] !== 'all') echo '<span class="dashicons dashicons-lock" style="font-size:14px; vertical-align:text-bottom;"></span> ' . ucfirst($row['role']);
                                    else echo 'Everyone';
                                ?>
                            </td>
                            <td><?php echo $row['expiry'] ? esc_html($row['expiry']) : '-'; ?></td>
                            <td><strong><?php echo $row['downloads']; ?></strong></td>
                            <td>
                                <a href="<?php echo get_edit_post_link( $row['product_id'] ); ?>" class="button button-small">Edit Product</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // --- Meta Box Logic (Same as before) ---

    public function register_meta_box() {
        add_meta_box( 'cw_product_files_box', 'Product Downloads & Attachments', array( $this, 'render_meta_box' ), 'product', 'normal', 'high' );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'cw_save_files_data', 'cw_files_nonce' );

        $names   = get_post_meta( $post->ID, 'wcpoa_attachment_name', true ) ?: [];
        $urls    = get_post_meta( $post->ID, 'wcpoa_attachment_url', true ) ?: [];
        $vis     = get_post_meta( $post->ID, 'cw_file_visibility', true ) ?: [];
        $status  = get_post_meta( $post->ID, 'cw_file_unlock_status', true ) ?: [];
        $roles   = get_post_meta( $post->ID, 'cw_file_role_restrict', true ) ?: [];
        $expiry  = get_post_meta( $post->ID, 'cw_file_expiry', true ) ?: [];
        $downloads = get_post_meta( $post->ID, 'cw_file_downloads', true ) ?: [];
        
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
            <p class="description" style="display:inline-block; margin-left:15px;">Shortcode: <code>[cirrusly_attachments]</code></p>
        </div>

        <table id="cw-files-table" class="widefat">
            <thead>
                <tr>
                    <th style="width:20px;"></th>
                    <th>File Label</th>
                    <th>File URL / ID</th>
                    <th>Visibility</th>
                    <th>Permissions</th>
                    <th>Order Status</th>
                    <th>Expiry</th>
                    <th style="width:60px;">Dl's</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="cw-files-tbody">
                <?php if ( ! empty( $names ) ) : foreach ( $names as $i => $name ) : 
                    $v = $vis[$i] ?? 'visible';
                    $s = $status[$i] ?? 'any';
                    $r = $roles[$i] ?? 'all';
                    $e = $expiry[$i] ?? '';
                    $d = $downloads[$i] ?? 0;
                ?>
                <tr class="cw-file-row">
                    <td><span class="dashicons dashicons-menu cw-row-handle"></span></td>
                    <td><input type="text" name="cw_custom_name[]" value="<?php echo esc_attr( $name ); ?>" class="cw-file-input" placeholder="Name" /></td>
                    <td><input type="text" name="cw_custom_url[]" value="<?php echo esc_attr( $urls[$i] ?? '' ); ?>" class="cw-file-url cw-file-input" placeholder="URL or ID" /></td>
                    
                    <td>
                        <select name="cw_file_vis[]">
                            <option value="visible" <?php selected($v, 'visible'); ?>>Visible</option>
                            <option value="hidden" <?php selected($v, 'hidden'); ?>>Hidden</option>
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

                    <td style="text-align:center; vertical-align:middle;">
                        <span class="cw-count-display"><?php echo intval($d); ?></span>
                        <input type="hidden" name="cw_file_downloads[]" value="<?php echo intval($d); ?>" />
                    </td>

                    <td>
                        <button type="button" class="button cw-upload-btn"><span class="dashicons dashicons-upload"></span></button>
                        <button type="button" class="button cw-remove-row"><span class="dashicons dashicons-trash"></span></button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <button type="button" class="button button-primary" id="cw-add-row" style="margin-top:10px;">Add Resource</button>
        <?php
    }

    public function save_data( $post_id ) {
        if ( ! isset( $_POST['cw_files_nonce'] ) || ! wp_verify_nonce( $_POST['cw_files_nonce'], 'cw_save_files_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if(isset($_POST['cw_attachments_placement'])) update_post_meta($post_id, 'cw_attachments_placement', sanitize_text_field($_POST['cw_attachments_placement']));
        if(isset($_POST['cw_attachments_tab_title'])) update_post_meta($post_id, 'cw_attachments_tab_title', sanitize_text_field($_POST['cw_attachments_tab_title']));

        $names = $_POST['cw_custom_name'] ?? [];
        $urls  = $_POST['cw_custom_url'] ?? [];
        $vis   = $_POST['cw_file_vis'] ?? [];
        $status= $_POST['cw_file_status'] ?? [];
        $roles = $_POST['cw_file_role_restrict'] ?? [];
        $expiry= $_POST['cw_file_expiry'] ?? [];
        $dloads= $_POST['cw_file_downloads'] ?? [];

        $clean_names = []; $clean_urls = []; $clean_vis = []; $clean_status = []; $clean_roles = []; $clean_expiry = []; $clean_dloads = [];

        for ( $i = 0; $i < count( $names ); $i++ ) {
            if ( ! empty( $names[$i] ) && ! empty( $urls[$i] ) ) {
                $clean_names[]  = sanitize_text_field( $names[$i] );
                $clean_urls[]   = sanitize_text_field( $urls[$i] );
                $clean_vis[]    = sanitize_text_field( $vis[$i] ?? 'visible' );
                $clean_status[] = sanitize_text_field( $status[$i] ?? 'any' );
                $clean_roles[]  = sanitize_text_field( $roles[$i] ?? 'all' );
                $clean_expiry[] = sanitize_text_field( $expiry[$i] ?? '' );
                $clean_dloads[] = sanitize_text_field( $dloads[$i] ?? 0 );
            }
        }

        update_post_meta( $post_id, 'wcpoa_attachment_name', $clean_names );
        update_post_meta( $post_id, 'wcpoa_attachment_url', $clean_urls );
        update_post_meta( $post_id, 'cw_file_visibility', $clean_vis );
        update_post_meta( $post_id, 'cw_file_unlock_status', $clean_status );
        update_post_meta( $post_id, 'cw_file_role_restrict', $clean_roles );
        update_post_meta( $post_id, 'cw_file_expiry', $clean_expiry );
        update_post_meta( $post_id, 'cw_file_downloads', $clean_dloads );
    }
}