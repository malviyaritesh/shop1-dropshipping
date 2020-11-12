<?php
/**
 * Plugin Name: WC Shop1
 * Plugin URI: https://owliverse.com
 * Description: WooCommerce integration for Shop1.
 * Version: 0.0.1
 * Author: Ritesh Malviya
 * Author URI: mailto:riteshmalviyam10@gmail.com
 * Text Domain: wc-shop1
 * Domain Path: /languages
 * License: GPL v3 or later.
 *
 * WC requires at least: 3.0.0
 *
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_SHOP1_PLUGIN_FILE', __FILE__ );
define( 'WC_SHOP1_VERSION', '0.0.1' );

require_once __DIR__ . '/vendor/autoload.php';

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	\WcShop1\WcShop1::instance();
}