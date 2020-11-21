<?php
/**
 * Plugin Name: Shop1 Dropshipping
 * Plugin URI: https://owliverse.com
 * Description: WooCommerce integration for Shop1.
 * Version: 0.0.1
 * Author: shop1.com
 * Author URI: http://shop1.com/
 * Text Domain: shop1-dropshipping
 * Domain Path: /languages
 * License: GPL v3 or later.
 *
 * WC requires at least: 4.0.0
 *
 */

defined( 'ABSPATH' ) || exit;

define( 'SHOP1_DROPSHIPPING_PLUGIN_FILE', __FILE__ );
define( 'SHOP1_DROPSHIPPING_VERSION', '0.0.1' );

require_once __DIR__ . '/vendor/autoload.php';

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	\Shop1Dropshipping\Shop1Dropshipping::instance();
}