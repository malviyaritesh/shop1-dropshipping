<?php
/**
 * Plugin Name: Shop1 Dropshipping
 * Plugin URI: http://shop1.com/
 * Description: WooCommerce integration for Shop1.
 * Version: 0.0.2
 * Author: shop1.com
 * Author URI: http://shop1.com/
 * Text Domain: shop1-dropshipping
 * Domain Path: /languages
 * License: GPL v3 or later.
 *
 * WC requires at least: 4.0.0
 *
 */

use Shop1Dropshipping\Admin\Admin;
use Shop1Dropshipping\Shop1Dropshipping;

defined( 'ABSPATH' ) || exit;

define( 'SHOP1_DROPSHIPPING_PLUGIN_FILE', __FILE__ );
define( 'SHOP1_DROPSHIPPING_VERSION', '0.0.2' );

require_once __DIR__ . '/vendor/autoload.php';

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	Shop1Dropshipping::instance();
} else {
	add_action( 'admin_notices', [ Admin::class, 'render_missing_or_outdated_wc_notice' ] );
}