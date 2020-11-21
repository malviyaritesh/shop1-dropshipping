<?php


namespace Shop1Dropshipping;


use Shop1Dropshipping\Admin\Admin;

final class Shop1Dropshipping {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		register_activation_hook( SHOP1_DROPSHIPPING_PLUGIN_FILE, [ Shop1DropshippingInstall::class, 'activate' ] );
		register_uninstall_hook( SHOP1_DROPSHIPPING_PLUGIN_FILE, [ Shop1DropshippingInstall::class, 'uninstall' ] );
		Admin::init_hooks();
	}
}