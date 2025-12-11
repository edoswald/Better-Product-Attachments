<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Cirrusly_Frontend {

    public function __construct() {
        // Enqueue Styles & Scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Determine Placement Logic
        add_action( 'wp', array( $this, 'setup_hooks' ) );
        
        // Order Page Logic
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_order_files' ) );

        // Shortcode
        add_shortcode( 'cirrusly_attachments', array( $this, 'render_shortcode' ) );

        // Email Integration
        add_action( 'woocommerce_email_after_order_table', array( $this, 'display_email_files' ), 10, 4 );

        // AJAX Handler for Tracking
        add_action( 'wp_ajax_cw_track_download', array( $this, 'ajax_track_download' ) );
        add_action( 'wp_ajax_nopriv_cw_track_download', array( $this, 'ajax_track_download' ) );
    }

    public function setup_hooks() {
        if ( ! is_product() ) return;

        global $post;
        $placement = get_post_meta( $post->ID, 'cw_attachments_placement', true ) ?: 'description';

        if ( $placement === 'tab' ) {
            add_filter( 'woocommerce_product_tabs', array( $this, 'add_attachment_tab' ) );
        } elseif ( $placement === 'after_cart' ) {
            add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_files_block' ) );
        } else {
            add_filter( 'the_content', array( $this, 'append_files_to_content' ), 20 );
        }
    }

    public function enqueue_assets() {
        if ( is_product() || is_account_page() ) {
            wp_enqueue_style( 'cirrusly-frontend-css', CIRRUSLY_URL . 'assets/css/frontend.css', array(), CIRRUSLY_VERSION );
            
            // Inline JS for click tracking to avoid extra file request
            $script = "
            jQuery(document).on('click', '.cw-file-link.cw-trackable', function(e) {
                var pid = jQuery(this).data('pid');
                var idx = jQuery(this).data('idx');
                jQuery.post('" . admin_url('admin-ajax.php') . "', {
                    action: 'cw_track_download',
                    product_id: pid,
                    file_index: idx
                });
            });
            ";
            wp_add_inline_script( 'jquery', $script );
        }
    }

    // --- Shortcode ---
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'product_id' => get_the_ID(),
        ), $atts );

        if ( ! $atts['product_id'] ) return '';
        return $this->get_files_html( $atts['product_id'] );
    }

    // --- AJAX Tracking ---
    public function ajax_track_download() {
        $pid = intval( $_POST['product_id'] );
        $idx = intval( $_POST['file_index'] );

        $downloads = get_post_meta( $pid, 'cw_file_downloads', true );
        if ( ! is_array( $downloads ) ) $downloads = [];

        if ( isset( $downloads[$idx] ) ) {
            $downloads[$idx] = intval( $downloads[$idx] ) + 1;
        } else {
            // Fill gaps if array is sparse
            $downloads[$idx] = 1;
        }

        update_post_meta( $pid, 'cw_file_downloads', $downloads );
        wp_die();
    }

    // --- Tab Logic ---
    public function add_attachment_tab( $tabs ) {
        global $post;
        $title = get_post_meta( $post->ID, 'cw_attachments_tab_title', true ) ?: 'Downloads';
        if ( $this->get_files_html( $post->ID ) ) {
            $tabs['cw_attachments'] = array(
                'title'    => $title,
                'priority' => 50,
                'callback' => array( $this, 'render_files_block' )
            );
        }
        return $tabs;
    }

    // --- Content Logic ---
    public function append_files_to_content( $content ) {
        if ( ! is_product() ) return $content;
        global $post;
        $html = $this->get_files_html( $post->ID );
        return $html ? $content . $html : $content;
    }

    public function render_files_block() {
        global $post;
        echo $this->get_files_html( $post->ID );
    }

    // --- Core HTML Generator (Updated for Tracking) ---
    private function get_files_html( $post_id ) {
        $names  = get_post_meta( $post_id, 'wcpoa_attachment_name', true );
        $ids    = get_post_meta( $post_id, 'wcpoa_attachment_url', true );
        $vis    = get_post_meta( $post_id, 'cw_file_visibility', true );
        $roles  = get_post_meta( $post_id, 'cw_file_role_restrict', true );
        $expiry = get_post_meta( $post_id, 'cw_file_expiry', true );

        if ( empty( $names ) || ! is_array( $names ) ) return '';

        $list_html = '';
        $count = 0;

        foreach ( $names as $i => $name ) {
            if ( empty( $name ) || empty( $ids[$i] ) ) continue;

            $v = $vis[$i] ?? 'visible';
            if ( $v === 'hidden' ) continue;

            $r = $roles[$i] ?? 'all';
            if ( ! Cirrusly_Helpers::check_permission($r) ) continue;

            $e = $expiry[$i] ?? '';
            if ( Cirrusly_Helpers::check_expiry($e) ) continue;

            $url = is_numeric( $ids[$i] ) ? wp_get_attachment_url( $ids[$i] ) : $ids[$i];
            
            if ( $url ) {
                $count++;
                $icon = Cirrusly_Helpers::get_icon( $url );
                $size = Cirrusly_Helpers::get_size_label( $ids[$i] );
                
                // Added data attributes and class cw-trackable
                $list_html .= '<li>
                    <a href="' . esc_url( $url ) . '" target="_blank" class="cw-file-link cw-trackable" data-pid="' . esc_attr($post_id) . '" data-idx="' . intval($i) . '">
                        <span class="dashicons ' . $icon . '"></span> 
                        <span class="cw-file-name">' . esc_html( $name ) . '</span>
                        ' . $size . '
                    </a>
                </li>';
            }
        }

        if ( $count > 0 ) {
            $wrapper = '<div class="cw-product-files-section">';
            $wrapper .= '<h3 class="cw-files-heading">Resources</h3>'; 
            $wrapper .= '<ul class="cw-files-list">' . $list_html . '</ul>';
            $wrapper .= '</div>';
            return $wrapper;
        }

        return '';
    }

    // --- Order Page Logic ---
    public function display_order_files( $order ) {
        $this->render_order_files($order, false);
    }

    // --- Email Logic ---
    public function display_email_files( $order, $sent_to_admin, $plain_text, $email ) {
        if ( $plain_text ) return; // Only support HTML emails for now
        $this->render_order_files($order, true);
    }

    // --- Shared Order/Email Renderer ---
    private function render_order_files( $order, $is_email = false ) {
        $items = $order->get_items();
        $output = '';
        $order_status = $order->get_status();

        foreach ( $items as $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;
            $pid = $product->get_id();

            $names  = get_post_meta( $pid, 'wcpoa_attachment_name', true );
            $ids    = get_post_meta( $pid, 'wcpoa_attachment_url', true );
            $reqs   = get_post_meta( $pid, 'cw_file_unlock_status', true );
            $expiry = get_post_meta( $pid, 'cw_file_expiry', true );

            if ( ! empty( $names ) ) {
                $links = [];
                foreach ( $names as $i => $n ) {
                    $req = $reqs[$i] ?? 'any';
                    // Simplify order status check
                    if ( $req === 'completed' && $order_status !== 'completed' ) continue;
                    if ( $req === 'processing' && ! in_array( $order_status, ['processing', 'completed'] ) ) continue;

                    $e = $expiry[$i] ?? '';
                    if ( Cirrusly_Helpers::check_expiry($e) ) continue;

                    $url = is_numeric( $ids[$i] ) ? wp_get_attachment_url( $ids[$i] ) : $ids[$i];
                    if ( $url ) {
                        // Note: We don't track downloads from emails/order page to keep it simple (no JS there)
                        $size = strip_tags( Cirrusly_Helpers::get_size_label( $ids[$i] ) );
                        $links[] = '<a href="' . esc_url( $url ) . '" target="_blank" style="text-decoration:underline;">' . esc_html( $n ) . ' ' . $size . '</a>';
                    }
                }
                if ( ! empty( $links ) ) {
                    $output .= '<div style="margin-bottom:10px;"><strong>' . esc_html( $product->get_name() ) . ':</strong><br>' . implode( '<br>', $links ) . '</div>';
                }
            }
        }

        if ( $output ) {
            $style = $is_email ? 'margin-top:20px; padding:15px; border:1px solid #e5e5e5; background:#f9f9f9;' : 'margin-top:20px; padding:15px; background:#f9f9f9; border-radius:5px;';
            echo '<section class="cw-order-files" style="' . $style . '">';
            echo '<h2 class="woocommerce-column__title" style="font-size:18px; margin-bottom:10px;">Included Downloads</h2>';
            echo $output;
            echo '</section>';
        }
    }
}