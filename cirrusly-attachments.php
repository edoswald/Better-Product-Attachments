<?php
/**
 * Plugin Name: Cirrusly Better Product Attachments
 * Description: Professional product attachments with visibility, role restriction, expiry, and flexible positioning. Matches top competitor functionality.
 * Version: 3.0
 * Author: Cirrusly
 * Text Domain: cirrusly-attachments
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CIRRUSLY_VERSION', '3.0.0' );
define( 'CIRRUSLY_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIRRUSLY_URL', plugin_dir_url( __FILE__ ) );

require_once CIRRUSLY_PATH . 'includes/class-cirrusly-helpers.php';
require_once CIRRUSLY_PATH . 'includes/class-cirrusly-admin.php';
require_once CIRRUSLY_PATH . 'includes/class-cirrusly-frontend.php';

function cirrusly_init() {
    new Cirrusly_Admin();
    new Cirrusly_Frontend();
}
add_action( 'plugins_loaded', 'cirrusly_init' );