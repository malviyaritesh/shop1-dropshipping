<?php


namespace WcShop1\Admin;


class Admin {

	const SHOP1_API_KEY_OPTION = 'wc_shop1_api_key';

	const SHOP1_MENU_SLUG = 'wc_shop1';
	const CONFIGURATIONS_SUBMENU_SLUG = self::SHOP1_MENU_SLUG . '-configurations';

	const SHOP1_CATALOG_URL = 'https://admin.shop1.com/marketplace/catalog/grid';
	const SHOP1_CONNECT_URL = 'https://admin.shop1.com/stores/third-party/connect';

	public static function init_hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'shop1_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
		add_action( 'admin_print_footer_scripts', [ __CLASS__, 'print_footer_scripts' ] );

		add_action( 'admin_post_connect-to-shop1', [ __CLASS__, 'handle_shop1_connect' ] );

		add_action( 'admin_post_shop1-connect-response', [ __CLASS__, 'shop1_connect_response' ] );
		add_action( 'admin_post_nopriv_shop1-connect-response', [ __CLASS__, 'shop1_connect_response' ] );

		add_action( 'wp_ajax_shop1-test-connection', [ __CLASS__, 'shop1_test_connection' ] );

		add_action( 'wp_ajax_shop1-product-added', [ __CLASS__, 'shop1_product_added' ] );
		add_action( 'wp_ajax_nopriv_shop1-product-added', [ __CLASS__, 'shop1_product_added' ] );

		add_action( 'wp_ajax_shop1-order-added', [ __CLASS__, 'shop1_order_added' ] );
		add_action( 'wp_ajax_nopriv_shop1-order-added', [ __CLASS__, 'shop1_order_added' ] );
	}

	public static function enqueue_scripts( $hook_suffix ) {
		if ( 'shop1_page_' . self::CONFIGURATIONS_SUBMENU_SLUG === $hook_suffix ) {
			wp_enqueue_script(
				'wc-shop1-configuration',
				plugins_url( '/assets/scripts/admin/page-configuration.js', WC_SHOP1_PLUGIN_FILE ),
				[ 'jquery' ],
				'1.0',
				true
			);
		}
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

	private static function log_to_db( $type, $identifier, $data, $complex_data = true ) {
		global $wpdb;

		return $wpdb->insert( "{$wpdb->prefix}wc_shop1_log", [
			'user_id'    => get_current_user_id(),
			'type'       => $type,
			'identifier' => $identifier,
			'payload'    => $complex_data ? serialize( $data ) : $data,
		] );
	}

	private static function get_unique_identifier() {
		return uniqid( wp_rand( 10000, 99999 ) );
	}

	public static function handle_shop1_connect() {
		if ( isset( $_GET['_nonce'] ) && wp_verify_nonce( $_GET['_nonce'], 'connect-to-shop1' ) ) {
			$identifier = self::get_unique_identifier();
			$args       = [
				'shop_name'           => get_bloginfo( 'name' ),
				'platform'            => 'WordPress/WooCommerce',
				'platform_url'        => home_url(),
				'scopes'              => 'read,write',
				'redirect_url'        => admin_url( 'admin-post.php?action=shop1-connect-response' ),
				'state'               => $identifier,
				'product_webhook_url' => admin_url( 'admin-ajax.php?action=shop1-product-added' ),
				'order_webhook_url'   => admin_url( 'admin-ajax.php?action=shop1-order-added' ),
			];
			self::log_to_db( 'shop1_connect_request', $identifier, $args );
			wp_redirect( add_query_arg( $args, self::SHOP1_CONNECT_URL ) );
			exit;
		} else {
			wp_die( 'Invalid link.' );
		}
	}

	private static function verify_identifier( $identifier ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_shop1_log
                WHERE type = 'shop1_connect_request' AND identifier = %s",
				$identifier
			)
		);
	}

	public static function shop1_connect_response() {
		if ( isset( $_GET['state'] ) && self::verify_identifier( $_GET['state'] )
		     && ! empty( $_GET['user_email'] )
		     && ! empty( $_GET['api_key'] )
		) {
			$data = [
				'api_key'    => $_GET['api_key'],
				'user_email' => $_GET['user_email'],
				'identifier' => $_GET['state'],
			];
			update_option( self::SHOP1_API_KEY_OPTION, $data );
			self::log_to_db( 'shop1_connect_response', $_GET['state'], $data );
			wp_redirect( admin_url( 'admin.php?page=' . self::CONFIGURATIONS_SUBMENU_SLUG ) );
			exit;
		} else {
			wp_die( 'Invalid or malformed request.' );
		}
	}

	public static function shop1_test_connection() {
		$api_key_data = (array) get_option( self::SHOP1_API_KEY_OPTION, [] );
		if ( empty( $api_key_data ) ) {
			wp_send_json_success( [
				'code'    => 'not_authenticated',
				'message' => __( 'Not authenticated.', 'wc-shop1' ),
			] );
		}
		$response = wp_remote_get( add_query_arg( [
			'api_key'  => $api_key_data['api_key'],
			'state'    => $api_key_data['identifier'],
			'platform' => 'WordPress/WooCommerce',
		], 'https://admin.shop1.com/api/stores/third-party/isconnect' ), [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
		] );
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response );
		}
		if ( 200 === $response['response']['code'] ) {
			$body = json_decode( $response['body'] );
			if ( $body && true === $body->success && $api_key_data['identifier'] === $body->state ) {
				wp_send_json_success( [
					'code'       => 'verified_successfully',
					'user_email' => $api_key_data['user_email'],
					'message'    => $body->success_message
				] );
			}
		}
		wp_send_json_error();
	}

	public static function shop1_order_added() {
		// Todo
	}

	public static function shop1_product_added() {
		// Todo
	}
}