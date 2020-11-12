<?php


namespace WcShop1\Admin;


class Admin {
	const SHOP1_MENU_SLUG = 'wc_shop1';
	const CONFIGURATIONS_SUBMENU_SLUG = self::SHOP1_MENU_SLUG . '-configurations';

	const SHOP1_CATALOG_URL = 'https://admin.shop1.com/marketplace/catalog/grid';
	const SHOP1_CONNECT_URL = 'https://admin.shop1.com/stores/third-party/connect';

	public static function init_hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'shop1_admin_menu' ] );
		add_action( 'admin_print_footer_scripts', [ __CLASS__, 'print_footer_scripts' ] );
		add_action( 'admin_post_connect-to-shop1', [ __CLASS__, 'handle_shop1_connect' ] );
	}

	public static function shop1_admin_menu() {
		global $submenu;

		add_menu_page(
			__( 'Shop1', 'wc-shop1' ),
			__( 'Shop1', 'wc-shop1' ),
			'manage_options',
			self::SHOP1_MENU_SLUG,
			'',
			'dashicons-store'
		);
		$submenu[ self::SHOP1_MENU_SLUG ][] = [ __( 'Catalog', 'wc-shop1' ), 'manage_options', self::SHOP1_CATALOG_URL ];
		add_submenu_page(
			self::SHOP1_MENU_SLUG,
			__( 'Configurations', 'wc-shop1' ),
			__( 'Configurations', 'wc-shop1' ),
			'manage_options',
			self::CONFIGURATIONS_SUBMENU_SLUG,
			[ __CLASS__, 'configurations_page' ]
		);
	}

	public static function configurations_page() {
		include dirname( WC_SHOP1_PLUGIN_FILE ) . '/templates/admin/page-configuration.php';
	}

	public static function print_footer_scripts() {
		?>
        <script>
            document.querySelector('#toplevel_page_wc_shop1 .wp-submenu a.wp-first-item').setAttribute('target', '_blank');
        </script>
		<?php
	}

	public static function handle_shop1_connect() {
		if ( isset( $_GET['_nonce'] ) && wp_verify_nonce( $_GET['_nonce'], 'connect-to-shop1' ) ) {
			wp_redirect( add_query_arg( [
				'shop_name'           => site_url(),
				'platform'            => 'WordPress/WooCommerce',
				'scopes'              => 'read,write',
				'redirect_url'        => admin_url( self::CONFIGURATIONS_SUBMENU_SLUG ),
				'nonce'               => '',
				'product_webhook_url' => '',
				'order_webhook_url'   => '',
			], self::SHOP1_CONNECT_URL ) );
		} else {
			wp_die( 'Invalid link.' );
		}
	}
}