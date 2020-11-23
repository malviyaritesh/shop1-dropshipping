<?php


namespace Shop1Dropshipping\Admin;


use Automattic\WooCommerce\RestApi\Utilities\ImageAttachment;

class Admin {

	const SHOP1_API_KEY_OPTION = 'shop1-dropshipping_api_key';
	const WC_ORDER_CREATED_WEBHOOK_ID_OPTION = 'shop1-dropshipping_order_created_webhook_id';
	const WC_ORDER_UPDATED_WEBHOOK_ID_OPTION = 'shop1-dropshipping_order_updated_webhook_id';

	const CONFIGURATIONS_SUBMENU_SLUG = 'shop1-dropshipping-configurations';

	private static $api_key_data;

	public static function init_hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'shop1_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
		add_action( 'admin_print_footer_scripts', [ __CLASS__, 'print_footer_scripts' ] );

		add_action( 'admin_post_connect-to-shop1', [ __CLASS__, 'handle_shop1_connect' ] );

		add_action( 'admin_post_shop1-connect-response', [ __CLASS__, 'shop1_connect_response' ] );
		add_action( 'admin_post_nopriv_shop1-connect-response', [ __CLASS__, 'shop1_connect_response' ] );

		add_action( 'wp_ajax_shop1-disconnect', [ __CLASS__, 'shop1_disconnect' ] );

		add_action( 'wp_ajax_shop1-test-connection', [ __CLASS__, 'shop1_test_connection' ] );

		add_action( 'wp_ajax_shop1-product-hook', [ __CLASS__, 'shop1_product_hook' ] );
		add_action( 'wp_ajax_nopriv_shop1-product-hook', [ __CLASS__, 'shop1_product_hook' ] );

		add_action( 'wp_ajax_shop1-order-hook', [ __CLASS__, 'shop1_order_hook' ] );
		add_action( 'wp_ajax_nopriv_shop1-order-hook', [ __CLASS__, 'shop1_order_hook' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( SHOP1_DROPSHIPPING_PLUGIN_FILE ), [ __CLASS__, 'add_plugin_action_links' ] );

		add_filter( 'woocommerce_webhook_payload', [ __CLASS__, 'filter_wc_webhook_payload' ], 10, 4 );
		add_filter( 'woocommerce_webhook_should_deliver', [ __CLASS__, 'filter_wc_webhook_should_deliver' ], 10, 3 );
	}

	public static function render_missing_or_outdated_wc_notice() {
		?>
        <div class="notice notice-error">
            <p>
                <strong>Shop1 Dropshipping</strong> plugin is a WooCommerce
                extension. Please install and activate
                <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>
                for this plugin to function properly.
            </p>
        </div>
		<?php
	}

	public static function enqueue_scripts( $hook_suffix ) {
		if ( 'toplevel_page_' . self::CONFIGURATIONS_SUBMENU_SLUG === $hook_suffix ) {
			wp_enqueue_script(
				'shop1-dropshipping-configuration',
				plugins_url( '/assets/scripts/admin/page-configuration.js', SHOP1_DROPSHIPPING_PLUGIN_FILE ),
				[ 'jquery' ],
				'1.0',
				true
			);
		}
	}

	public static function add_plugin_action_links( $actions ) {
		return array_merge( [
			'configure' => '<a href="' . admin_url( 'admin.php?page=' . self::CONFIGURATIONS_SUBMENU_SLUG ) . '">' . __( 'Configure', 'shop1-dropshipping' ) . '</a>',
		], $actions );
	}

	public static function shop1_admin_menu() {
		global $submenu;

		add_menu_page(
			__( 'Shop1', 'shop1-dropshipping' ),
			__( 'Shop1', 'shop1-dropshipping' ),
			'manage_options',
			self::CONFIGURATIONS_SUBMENU_SLUG,
			'',
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU4IiBoZWlnaHQ9IjI4NCIgdmlld0JveD0iMCAwIDI1OCAyODQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxnIG9wYWNpdHk9IjAuOTQiPgo8ZyBvcGFjaXR5PSIwLjk0Ij4KPGcgb3BhY2l0eT0iMC45NCI+CjxnIG9wYWNpdHk9IjAuOTQiPgo8cGF0aCBvcGFjaXR5PSIwLjk0IiBkPSJNMjU3LjI5NCA4Ny44MDQ5QzI1Ny4yOTQgMTA3LjM3MyAyNDEuNDMzIDEyMy4yMzQgMjIxLjg2NSAxMjMuMjM0QzIwMi4yOTcgMTIzLjIzNCAxODYuNDM0IDEwNy4zNzMgMTg2LjQzNCA4Ny44MDQ5QzE4Ni40MzQgNjguMjM2OSAyMDIuMjk3IDUyLjM3NTYgMjIxLjg2NSA1Mi4zNzU2QzI0MS40MzMgNTIuMzc1NiAyNTcuMjk0IDY4LjIzNjkgMjU3LjI5NCA4Ny44MDQ5WiIgZmlsbD0iIzY2MkQ5MSIvPgo8L2c+CjxnIG9wYWNpdHk9IjAuOTQiPgo8cGF0aCBvcGFjaXR5PSIwLjk0IiBkPSJNMTc0LjExMSAyMS41NjY3QzE3NC4xMTEgMzMuNDc3NCAxNjQuNDU2IDQzLjEzMjEgMTUyLjU0NSA0My4xMzIxQzE0MC42MzUgNDMuMTMyMSAxMzAuOTc5IDMzLjQ3NzQgMTMwLjk3OSAyMS41NjY3QzEzMC45NzkgOS42NTYwOCAxNDAuNjM1IDguMzk1MTFlLTA1IDE1Mi41NDUgOC4zOTUxMWUtMDVDMTY0LjQ1NiA4LjM5NTExZS0wNSAxNzQuMTExIDkuNjU2MDggMTc0LjExMSAyMS41NjY3WiIgZmlsbD0iIzY2MkQ5MSIvPgo8L2c+CjxnIG9wYWNpdHk9IjAuOTQiPgo8cGF0aCBvcGFjaXR5PSIwLjk0IiBkPSJNMjE3Ljk1OSAxNTIuMTA2SDIxMy41MjNWMTUwLjUwM0gyMjQuMzIxVjE1Mi4xMDZIMjE5Ljg2M1YxNjUuMDg4SDIxNy45NTlWMTUyLjEwNloiIGZpbGw9IiM2NjJEOTEiLz4KPC9nPgo8ZyBvcGFjaXR5PSIwLjk0Ij4KPHBhdGggb3BhY2l0eT0iMC45NCIgZD0iTTIzOC4yMzcgMTU4LjY4M0MyMzguMTI5IDE1Ni42NDkgMjM3Ljk5NyAxNTQuMjA0IDIzOC4wMjEgMTUyLjM4NUgyMzcuOTU0QzIzNy40NTcgMTU0LjA5NiAyMzYuODUyIDE1NS45MTMgMjM2LjExNiAxNTcuOTI3TDIzMy41NDEgMTY1LjAwMUgyMzIuMTEzTDIyOS43NTQgMTU4LjA1NUMyMjkuMDYxIDE1NiAyMjguNDc3IDE1NC4xMTcgMjI4LjA2NiAxNTIuMzg1SDIyOC4wMjRDMjI3Ljk4IDE1NC4yMDQgMjI3Ljg3MiAxNTYuNjQ5IDIyNy43NDIgMTU4LjgzNUwyMjcuMzUyIDE2NS4wODlIMjI1LjU1NkwyMjYuNTczIDE1MC41MDNIMjI4Ljk3NkwyMzEuNDYyIDE1Ny41NTdDMjMyLjA2OSAxNTkuMzU1IDIzMi41NjYgMTYwLjk1NSAyMzIuOTM0IDE2Mi40NzFIMjMyLjk5OEMyMzMuMzY4IDE2MC45OTcgMjMzLjg4NiAxNTkuMzk3IDIzNC41MzYgMTU3LjU1N0wyMzcuMTMzIDE1MC41MDNIMjM5LjUzNkwyNDAuNDQyIDE2NS4wODlIMjM4LjYwNEwyMzguMjM3IDE1OC42ODNaIiBmaWxsPSIjNjYyRDkxIi8+CjwvZz4KPGcgb3BhY2l0eT0iMC45NCI+CjxwYXRoIG9wYWNpdHk9IjAuOTQiIGQ9Ik0xNDYuNjA0IDI1MS4wODJINTcuODA0VjIyMi41MjFIODcuMjMwN1YxMzcuMjYySDY1LjkzMlYxMTAuODM4SDExNS42MjhWMjIyLjUyMUgxNDYuNjA0VjI1MS4wODJaTTEwMi4yMDQgNzguNzU0M0M0NS43NTg3IDc4Ljc1NDMgMCAxMjQuNTE0IDAgMTgwLjk2QzAgMjM3LjQwNiA0NS43NTg3IDI4My4xNjUgMTAyLjIwNCAyODMuMTY1QzE1OC42NTEgMjgzLjE2NSAyMDQuNDA5IDIzNy40MDYgMjA0LjQwOSAxODAuOTZDMjA0LjQwOSAxMjQuNTE0IDE1OC42NTEgNzguNzU0MyAxMDIuMjA0IDc4Ljc1NDNaIiBmaWxsPSIjNjYyRDkxIi8+CjwvZz4KPC9nPgo8L2c+CjwvZz4KPC9zdmc+Cg=='
		);
		add_submenu_page(
			self::CONFIGURATIONS_SUBMENU_SLUG,
			__( 'Configurations', 'shop1-dropshipping' ),
			__( 'Configurations', 'shop1-dropshipping' ),
			'manage_options',
			self::CONFIGURATIONS_SUBMENU_SLUG,
			[ __CLASS__, 'configurations_page' ]
		);
		$submenu[ self::CONFIGURATIONS_SUBMENU_SLUG ][] = [ __( 'Catalog', 'shop1-dropshipping' ), 'manage_options', 'https://admin.shop1.com/marketplace/catalog/grid' ];
	}

	public static function configurations_page() {
		include dirname( SHOP1_DROPSHIPPING_PLUGIN_FILE ) . '/templates/admin/page-configuration.php';
	}

	public static function print_footer_scripts() {
		?>
        <script>
            document.querySelector('#toplevel_page_<?php echo self::CONFIGURATIONS_SUBMENU_SLUG; ?> .wp-submenu li:last-of-type a').setAttribute('target', '_blank');
        </script>
		<?php
	}

	public static function create_wc_order_webhooks() {
		$api_key_data = self::get_api_key_data();
		if ( isset( $api_key_data['api_key'] ) ) {
			$topics       = [
				'order.created' => [
					'name'        => 'Send new order to Shop1',
					'option_name' => self::WC_ORDER_CREATED_WEBHOOK_ID_OPTION,
				],
				'order.updated' => [
					'name'        => 'Send updated order to Shop1',
					'option_name' => self::WC_ORDER_UPDATED_WEBHOOK_ID_OPTION,
				],
			];
			$delivery_url = add_query_arg( [
				'api_key'  => $api_key_data['api_key'],
				'state'    => $api_key_data['identifier'],
				'platform' => 'WordPress/WooCommerce',
			], 'https://admin.shop1.com/api/orders/new' );
			$user_id      = get_current_user_id();

			foreach ( $topics as $topic => $val ) {
				if ( false === get_option( $val['option_name'] ) ) {
					$webhook = new \WC_Webhook();
					$webhook->set_name( $val['name'] );
					$webhook->set_user_id( $user_id );
					$webhook->set_topic( $topic );
					$webhook->set_secret( self::get_unique_identifier() );
					$webhook->set_delivery_url( $delivery_url );
					$webhook->set_status( 'active' );
					$webhook->save();
					update_option( $val['option_name'], $webhook->get_id() );
				}
			}
		}
	}

	private static function remove_wc_order_webhooks() {
		$webhook_options = [
			self::WC_ORDER_CREATED_WEBHOOK_ID_OPTION,
			self::WC_ORDER_UPDATED_WEBHOOK_ID_OPTION,
		];
		foreach ( $webhook_options as $webhook_id_option ) {
			$webhook_id = get_option( $webhook_id_option );
			if ( $webhook_id ) {
				$webhook = null;
				try {
					$webhook = wc_get_webhook( $webhook_id );
				} catch ( \Exception $e ) {
					continue;
				}
				if ( $webhook ) {
					$webhook->delete( true );
					delete_option( $webhook_id_option );
				}
			}
		}
	}

	private static function is_active_shop1_webhook( $webhook_id ) {
		return in_array( $webhook_id, [
			absint( get_option( self::WC_ORDER_CREATED_WEBHOOK_ID_OPTION ) ),
			absint( get_option( self::WC_ORDER_UPDATED_WEBHOOK_ID_OPTION ) ),
		], true );
	}

	public static function filter_wc_webhook_payload( $payload, $resource, $resource_id, $webhook_id ) {
		if ( self::is_active_shop1_webhook( $webhook_id )
		     && $resource === 'order'
		     && isset( $payload['id'], $payload['order_key'], $payload['status'] )
		     && 'completed' === $payload['status']
		) {
			$identifier = $payload['order_key'];
			$payload    = [
				'platform_order_id'   => $payload['id'],
				'customer_first_name' => $payload['billing']['first_name'],
				'customer_last_name'  => $payload['billing']['last_name'],
				'customer_email'      => $payload['billing']['email'],
				'customer_phone'      => $payload['billing']['phone'],
				'billing_address'     => [
					'line_1'  => $payload['billing']['address_1'],
					'line_2'  => $payload['billing']['address_2'],
					'city'    => $payload['billing']['city'],
					'state'   => $payload['billing']['state'],
					'country' => $payload['billing']['country'],
					'zip'     => $payload['billing']['postcode'],
				],
				'sub_total'           => $payload['total'],
				'shipping_cost'       => $payload['shipping_total'],
				'discount'            => $payload['discount_total'],
				'tax'                 => $payload['total_tax'],
				'total'               => $payload['total'],
				'shipping_method'     => isset( $payload['shipping_lines'] ) && count( $payload['shipping_lines'] ) > 0
					? $payload['shipping_lines'][0]['method_title']
					: null,
				'payment_method'      => $payload['payment_method'],
				'currency'            => $payload['currency'],
				'products'            => array_map( function ( $product ) {
					return [
						'id'            => $product['id'],
						'sku'           => $product['sku'],
						'unit_price'    => wc_format_decimal( $product['price'] ),
						'qty'           => $product['quantity'],
						'line_total'    => $product['subtotal'],
						'tax'           => $product['total_tax'],
						'discount'      => null,
						'product_title' => $product['name'],
						'cost'          => null,
					];
				}, $payload['line_items'] ),
			];
			self::log_to_db( 'shop1_wc_order_webhook', $identifier, $payload );
		}

		return $payload;
	}

	public static function filter_wc_webhook_should_deliver( $should_deliver, \WC_Webhook $webhook, $arg ) {
		if ( self::is_active_shop1_webhook( $webhook->get_id() ) ) {
			$order = wc_get_order( $arg );
			if ( is_a( $order, \WC_Order::class ) && 'completed' !== $order->get_status() ) {
				$should_deliver = false;
			}
		}

		return $should_deliver;
	}

	private static function log_to_db( $type, $identifier, $data, $complex_data = true ) {
		global $wpdb;

		return $wpdb->insert( "{$wpdb->prefix}shop1_dropshipping_log", [
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
				'product_webhook_url' => admin_url( 'admin-ajax.php?action=shop1-product-hook' ),
				'order_webhook_url'   => admin_url( 'admin-ajax.php?action=shop1-order-hook' ),
			];
			self::log_to_db( 'shop1_connect_request', $identifier, $args );
			wp_redirect( add_query_arg( $args, 'https://admin.shop1.com/stores/third-party/connect' ) );
			exit;
		} else {
			wp_die( 'Invalid link.' );
		}
	}

	private static function verify_identifier( $identifier ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}shop1_dropshipping_log
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
			self::create_wc_order_webhooks();
			self::log_to_db( 'shop1_connect_response', $_GET['state'], $data );
			wp_redirect( admin_url( 'admin.php?page=' . self::CONFIGURATIONS_SUBMENU_SLUG ) );
			exit;
		} else {
			wp_die( 'Invalid or malformed request.' );
		}
	}

	private static function get_api_key_data() {
		if ( empty( self::$api_key_data ) ) {
			self::$api_key_data = (array) get_option( self::SHOP1_API_KEY_OPTION, [] );
		}

		return self::$api_key_data;
	}

	public static function remove_api_key_data() {
		delete_option( self::SHOP1_API_KEY_OPTION );
		self::$api_key_data = null;
	}

	public static function cleanup_on_disconnect() {
		self::remove_wc_order_webhooks();
		self::remove_api_key_data();
		self::remove_products( self::get_all_shop1_products() );
	}

	public static function shop1_disconnect() {
		$api_key_data = self::get_api_key_data();
		if ( empty( $api_key_data ) ) {
			wp_send_json_error();
		}
		$response = wp_remote_post( add_query_arg( [
			'state'    => $api_key_data['identifier'],
			'platform' => 'WordPress/WooCommerce',
			'api_key'  => $api_key_data['api_key'],
		], 'https://admin.shop1.com/api/stores/third-party/disconnect' ) );
		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
			$body = json_decode( $response['body'] );
			if ( true === $body->success && $api_key_data['identifier'] === $body->state ) {
				self::cleanup_on_disconnect();
				self::log_to_db( 'shop1_disconnect_response', $api_key_data['identifier'], $body );
				wp_send_json_success( [
					'code'    => 'disconnected',
					'message' => __( 'Disconnected successfully.', 'shop1-dropshipping' ),
				] );
			}
		}
		wp_send_json_error();
	}

	public static function shop1_test_connection() {
		$api_key_data = self::get_api_key_data();
		if ( empty( $api_key_data ) ) {
			wp_send_json_success( [
				'code'    => 'not_authenticated',
				'message' => __( 'Not authenticated.', 'shop1-dropshipping' ),
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
		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( $response['body'] );
			if ( $api_key_data['identifier'] === $body->state ) {
				if ( 200 === $response['response']['code'] && $body && true === $body->success ) {
					wp_send_json_success( [
						'code'       => 'verified_successfully',
						'user_email' => $api_key_data['user_email'],
						'message'    => $body->success_message
					] );
				} else {
					self::remove_api_key_data();
					wp_send_json_success( [
						'code'    => 'not_found',
						'message' => __( 'No matching record found.' ),
					] );
				}
			}
		}
		wp_send_json_error();
	}

	public static function shop1_order_hook() {
		// Todo
	}

	private static function parse_and_verify_json_body() {
		$output   = null;
		$json_str = file_get_contents( 'php://input' );
		if ( strlen( $json_str ) > 0 ) {
			$body = json_decode( $json_str, true );
			if ( is_array( $body ) && isset( $body['state'], $body['action'], $body['api_key'] ) ) {
				$api_key_data = self::get_api_key_data();
				if ( ! empty( $api_key_data ) && $api_key_data['api_key'] === $body['api_key'] ) {
					return $body;
				}

				return new \WP_Error( 'authentication_failed', __( 'Failed to verify the authenticity of the request.' ) );
			}
		}

		return new \WP_Error( 'invalid_json', __( 'Missing or invalid JSON body.' ) );
	}

	private static function insert_products( $products ) {
		$errors = [];
		foreach ( $products as $product ) {
			$wc_product = new \WC_Product();
			try {
				$wc_product->set_sku( $product['SKU'] );
			} catch ( \WC_Data_Exception $e ) {
				array_push( $errors, $e->getMessage() );
				continue;
			}
			$wc_product->set_name( $product['Name'] );
			$wc_product->set_description( $product['Description'] );
			$wc_product->set_regular_price( $product['Price'] );
			$wc_product->set_stock_quantity( $product['QTY'] );
			$wc_product->set_manage_stock( true );
			$tag_ids = [];
			foreach ( $product['tags'] as $tag ) {
				$tag_id = wp_create_tag( $tag['name'] );
				if ( is_wp_error( $tag_id ) ) {
					array_push( $errors, $tag_id );
					continue;
				}
				if ( is_array( $tag_id ) ) {
					$tag_id = $tag_id['term_id'];
				}
				array_push( $tag_ids, $tag_id );
			}
			$wc_product->set_tag_ids( $tag_ids );
			$wc_product->save();
			$images = [];
			foreach ( $product['Images'] as $image ) {
				$image_attachment = new ImageAttachment( 0, $wc_product->get_id() );
				try {
					$image_attachment->upload_image_from_src( $image['url'] );
				} catch ( \WC_REST_Exception $e ) {
					array_push( $errors, $e->getMessage() );
					continue;
				}
				array_push( $images, $image_attachment->id );
			}
			if ( count( $images ) > 0 ) {
				$wc_product->set_image_id( array_shift( $images ) );
			}
			if ( count( $images ) > 0 ) {
				$wc_product->set_gallery_image_ids( $images );
			}
			$wc_product->save();
			self::log_to_db( 'shop1_add_products', $wc_product->get_id(), $product );
		}

		return $errors;
	}

	private static function get_all_shop1_products() {
		global $wpdb;

		$rows   = $wpdb->get_results(
			"SELECT payload FROM {$wpdb->prefix}shop1_dropshipping_log
                WHERE type = 'shop1_add_products'", ARRAY_A
		);
		$output = [];
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$products = maybe_unserialize( $row['payload'] );
				if ( is_array( $products ) ) {
					foreach ( $products as $product ) {
						array_push( $output, $product['SKU'] );
					}
				}
			}
		}

		return $output;
	}

	private static function remove_products( $products ) {
		foreach ( $products as $product ) {
			$wc_product = wc_get_product( wc_get_product_id_by_sku( $product ) );
			if ( is_a( $wc_product, \WC_Product::class ) ) {
				$wc_product->delete( true );
				self::log_to_db( 'shop1_remove_products', $wc_product->get_id(), $product );
			}
		}
	}

	public static function shop1_product_hook() {
		$body   = self::parse_and_verify_json_body();
		$errors = [];
		if ( is_wp_error( $body ) ) {
			array_push( $errors, $body );
		}
		if ( 'addProduct' === $body['action'] ) {
			array_merge( $errors, self::insert_products( $body['products'] ) );
		} elseif ( 'removeProduct' === $body['action'] ) {
			self::remove_products( $body['products'] );
		}
		if ( empty( $errors ) ) {
			wp_send_json_success();
		}
		wp_send_json_error( $errors );
	}
}