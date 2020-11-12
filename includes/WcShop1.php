<?php


namespace WcShop1;


use WcShop1\Admin\Admin;

final class WcShop1 {
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
		register_activation_hook( WC_SHOP1_PLUGIN_FILE, [ WcShop1Install::class, 'activate' ] );
		Admin::init_hooks();
	}
}