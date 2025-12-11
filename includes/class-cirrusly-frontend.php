<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Cirrusly_Frontend {

    public function __construct() {
        // Enqueue Styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

        // Determine Placement Logic
        add_action( 'wp', array( $this, 'setup_hooks' ) );
        
        // Order Page Logic (Always Active)
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_order_files' ) );
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
            // Default: Description
            add_filter( 'the_content', array( $this, 'append_files_to_content' ), 20 );
        }
    }

    public function enqueue_styles() {
        if ( is_product() || is_account_page() ) {
            wp_enqueue_style( 'cirrusly-frontend-css', CIRRUSLY_URL . 'assets/css/frontend.css', array(), CIRRUSLY_VERSION );
        }
    }

    // --- Tab Logic ---
    public function add_attachment_tab( $tabs ) {
        global $post;
        $title = get_post_meta( $post->ID, 'cw_attachments_tab_title', true ) ?: 'Downloads';
        
        // Only add tab if content exists
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
        if ( $html ) {
            return $content . $html;
        }
        return $content;
    }

    // --- Render Block ---
    public function render_files_block() {
        global $post;
        echo $this->get_files_html( $post->ID );
    }

    // --- Core HTML Generator ---
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

            // 1. Visibility Check (Product Page)
            $v = $vis[$i] ?? 'visible';
            if ( $v === 'hidden' ) continue;

            // 2. Role Check
            $r = $roles[$i] ?? 'all';
            if ( ! Cirrusly_Helpers::check_permission($r) ) continue;

            // 3. Expiry Check
            $e = $expiry[$i] ?? '';
            if ( Cirrusly_Helpers::check_expiry($e) ) continue;

            // Build Link
            $url = is_numeric( $ids[$i] ) ? wp_get_attachment_url( $ids[$i] ) : $ids[$i];
            
            if ( $url ) {
                $count++;
                $icon = Cirrusly_Helpers::get_icon( $url );
                $size = Cirrusly_Helpers::get_size_label( $ids[$i] );
                
                $list_html .= '<li>
                    <a href="' . esc_url( $url ) . '" target="_blank" class="cw-file-link">
                        <span class="dashicons ' . $icon . '"></span> 
                        <span class="cw-file-name">' . esc_html( $name ) . '</span>
                        ' . $size . '
                    </a>
                </li>';
            }
        }

        if ( $count > 0 ) {
            // Check if title is needed (Tabs usually handle their own title)
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
                    // Order Status Logic
                    $req = $reqs[$i] ?? 'any';
                    if ( $req === 'completed' && $order_status !== 'completed' ) continue;
                    if ( $req === 'processing' && ! in_array( $order_status, ['processing', 'completed'] ) ) continue;

                    // Expiry Check
                    $e = $expiry[$i] ?? '';
                    if ( Cirrusly_Helpers::check_expiry($e) ) continue;

                    $url = is_numeric( $ids[$i] ) ? wp_get_attachment_url( $ids[$i] ) : $ids[$i];
                    if ( $url ) {
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
            echo '<section class="cw-order-files" style="margin-top:20px; padding:15px; background:#f9f9f9; border-radius:5px;">';
            echo '<h2 class="woocommerce-column__title">Downloads</h2>';
            echo $output;
            echo '</section>';
        }
    }
}