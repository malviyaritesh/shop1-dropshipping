<?php


namespace Shop1Dropshipping\Admin;


use Automattic\WooCommerce\RestApi\Utilities\ImageAttachment;

class Admin {

	const SHOP1_CONNECT_NONCE_OPTION = 'shop1-dropshipping_connect_nonce';
	const SHOP1_API_KEY_OPTION = 'shop1-dropshipping_api_key';
	const WC_ORDER_CREATED_WEBHOOK_ID_OPTION = 'shop1-dropshipping_order_created_webhook_id';
	const WC_ORDER_UPDATED_WEBHOOK_ID_OPTION = 'shop1-dropshipping_order_updated_webhook_id';
	const SHOP1_PRODUCT_IDS_OPTION = 'shop1-dropshipping_product_ids';

	const CONFIGURATIONS_SUBMENU_SLUG = 'shop1-dropshipping-configurations';

	const BATCH_PROCESSING_LIMIT = 100;

	private static $api_key_data;

	public static function init_hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'shop1_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
		add_action( 'admin_print_footer_scripts', [ __CLASS__, 'print_footer_scripts' ] );

		add_action( 'admin_post_shop1-connect-response', [ __CLASS__, 'shop1_connect_response' ] );
		add_action( 'admin_post_nopriv_shop1-connect-response', [ __CLASS__, 'shop1_connect_response' ] );

		add_action( 'wp_ajax_shop1-disconnect', [ __CLASS__, 'shop1_disconnect' ] );

		add_action( 'wp_ajax_shop1-test-connection', [ __CLASS__, 'shop1_test_connection' ] );

		add_action( 'wp_ajax_shop1-product-hook', [ __CLASS__, 'shop1_product_hook' ] );
		add_action( 'wp_ajax_nopriv_shop1-product-hook', [ __CLASS__, 'shop1_product_hook' ] );

		add_action( 'schedule_remove_shop1_products', [ __CLASS__, 'schedule_remove_shop1_products' ], 10, 2 );

		add_action( 'wp_ajax_shop1-order-hook', [ __CLASS__, 'shop1_order_hook' ] );
		add_action( 'wp_ajax_nopriv_shop1-order-hook', [ __CLASS__, 'shop1_order_hook' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( SHOP1_DROPSHIPPING_PLUGIN_FILE ), [ __CLASS__, 'add_plugin_action_links' ] );

		add_filter( 'woocommerce_webhook_payload', [ __CLASS__, 'filter_wc_webhook_payload' ], 10, 4 );
	}

	public static function render_missing_or_outdated_wc_notice() {
		?>
        <div class="notice notice-error">
            <p>
                <strong>Shop1 Dropshipping</strong> plugin is a WooCommerce
                extension. Please install and activate
                <a href="https://wordpress.org/plugins/woocommerce/"
                   target="_blank">WooCommerce</a>
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
				'1.0.1',
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
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU4IiBoZWlnaHQ9I' .
			'jI4NCIgdmlld0JveD0iMCAwIDI1OCAyODQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRw' .
			'Oi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgICA8Zz4KICAgICAgICA8Zz4KICAgICA' .
			'gICAgICAgPGc+CiAgICAgICAgICAgICAgICA8Zz4KICAgICAgICAgICAgICAgICAgIC' .
			'A8cGF0aCBkPSJNMjU3LjI5NCA4Ny44MDQ5QzI1Ny4yOTQgMTA3LjM3MyAyNDEuNDMzI' .
			'DEyMy4yMzQgMjIxLjg2NSAxMjMuMjM0QzIwMi4yOTcgMTIzLjIzNCAxODYuNDM0IDEw' .
			'Ny4zNzMgMTg2LjQzNCA4Ny44MDQ5QzE4Ni40MzQgNjguMjM2OSAyMDIuMjk3IDUyLjM' .
			'3NTYgMjIxLjg2NSA1Mi4zNzU2QzI0MS40MzMgNTIuMzc1NiAyNTcuMjk0IDY4LjIzNj' .
			'kgMjU3LjI5NCA4Ny44MDQ5WiIKICAgICAgICAgICAgICAgICAgICAgICAgICBmaWxsP' .
			'SIjZmZmZmZmIi8+CiAgICAgICAgICAgICAgICA8L2c+CiAgICAgICAgICAgICAgICA8' .
			'Zz4KICAgICAgICAgICAgICAgICAgICA8cGF0aCBkPSJNMTc0LjExMSAyMS41NjY3QzE' .
			'3NC4xMTEgMzMuNDc3NCAxNjQuNDU2IDQzLjEzMjEgMTUyLjU0NSA0My4xMzIxQzE0MC' .
			'42MzUgNDMuMTMyMSAxMzAuOTc5IDMzLjQ3NzQgMTMwLjk3OSAyMS41NjY3QzEzMC45N' .
			'zkgOS42NTYwOCAxNDAuNjM1IDguMzk1MTFlLTA1IDE1Mi41NDUgOC4zOTUxMWUtMDVD' .
			'MTY0LjQ1NiA4LjM5NTExZS0wNSAxNzQuMTExIDkuNjU2MDggMTc0LjExMSAyMS41NjY' .
			'3WiIKICAgICAgICAgICAgICAgICAgICAgICAgICBmaWxsPSIjZmZmZmZmIi8+CiAgIC' .
			'AgICAgICAgICAgICA8L2c+CiAgICAgICAgICAgICAgICA8Zz4KICAgICAgICAgICAgI' .
			'CAgICAgICA8cGF0aCBkPSJNMjE3Ljk1OSAxNTIuMTA2SDIxMy41MjNWMTUwLjUwM0gy' .
			'MjQuMzIxVjE1Mi4xMDZIMjE5Ljg2M1YxNjUuMDg4SDIxNy45NTlWMTUyLjEwNloiCiA' .
			'gICAgICAgICAgICAgICAgICAgICAgICAgZmlsbD0iI2ZmZmZmZiIvPgogICAgICAgIC' .
			'AgICAgICAgPC9nPgogICAgICAgICAgICAgICAgPGc+CiAgICAgICAgICAgICAgICAgI' .
			'CAgPHBhdGggZD0iTTIzOC4yMzcgMTU4LjY4M0MyMzguMTI5IDE1Ni42NDkgMjM3Ljk5' .
			'NyAxNTQuMjA0IDIzOC4wMjEgMTUyLjM4NUgyMzcuOTU0QzIzNy40NTcgMTU0LjA5NiA' .
			'yMzYuODUyIDE1NS45MTMgMjM2LjExNiAxNTcuOTI3TDIzMy41NDEgMTY1LjAwMUgyMz' .
			'IuMTEzTDIyOS43NTQgMTU4LjA1NUMyMjkuMDYxIDE1NiAyMjguNDc3IDE1NC4xMTcgM' .
			'jI4LjA2NiAxNTIuMzg1SDIyOC4wMjRDMjI3Ljk4IDE1NC4yMDQgMjI3Ljg3MiAxNTYu' .
			'NjQ5IDIyNy43NDIgMTU4LjgzNUwyMjcuMzUyIDE2NS4wODlIMjI1LjU1NkwyMjYuNTc' .
			'zIDE1MC41MDNIMjI4Ljk3NkwyMzEuNDYyIDE1Ny41NTdDMjMyLjA2OSAxNTkuMzU1ID' .
			'IzMi41NjYgMTYwLjk1NSAyMzIuOTM0IDE2Mi40NzFIMjMyLjk5OEMyMzMuMzY4IDE2M' .
			'C45OTcgMjMzLjg4NiAxNTkuMzk3IDIzNC41MzYgMTU3LjU1N0wyMzcuMTMzIDE1MC41' .
			'MDNIMjM5LjUzNkwyNDAuNDQyIDE2NS4wODlIMjM4LjYwNEwyMzguMjM3IDE1OC42ODN' .
			'aIgogICAgICAgICAgICAgICAgICAgICAgICAgIGZpbGw9IiNmZmZmZmYiLz4KICAgIC' .
			'AgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgICAgIDxnPgogICAgICAgICAgICAgI' .
			'CAgICAgIDxwYXRoIGQ9Ik0xNDYuNjA0IDI1MS4wODJINTcuODA0VjIyMi41MjFIODcu' .
			'MjMwN1YxMzcuMjYySDY1LjkzMlYxMTAuODM4SDExNS42MjhWMjIyLjUyMUgxNDYuNjA' .
			'0VjI1MS4wODJaTTEwMi4yMDQgNzguNzU0M0M0NS43NTg3IDc4Ljc1NDMgMCAxMjQuNT' .
			'E0IDAgMTgwLjk2QzAgMjM3LjQwNiA0NS43NTg3IDI4My4xNjUgMTAyLjIwNCAyODMuM' .
			'TY1QzE1OC42NTEgMjgzLjE2NSAyMDQuNDA5IDIzNy40MDYgMjA0LjQwOSAxODAuOTZD' .
			'MjA0LjQwOSAxMjQuNTE0IDE1OC42NTEgNzguNzU0MyAxMDIuMjA0IDc4Ljc1NDNaIgo' .
			'gICAgICAgICAgICAgICAgICAgICAgICAgIGZpbGw9IiNmZmZmZmYiLz4KICAgICAgIC' .
			'AgICAgICAgIDwvZz4KICAgICAgICAgICAgPC9nPgogICAgICAgIDwvZz4KICAgIDwvZ' .
			'z4KPC9zdmc+Cg=='
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

			// Remove any existing webhooks, so there are no orphaned
			// entries present and we don't create duplicates.
			self::remove_wc_order_webhooks();

			foreach ( $topics as $topic => $val ) {
				$webhook = new \WC_Webhook();
				$webhook->set_name( $val['name'] );
				$webhook->set_user_id( $user_id );
				$webhook->set_topic( $topic );
				$webhook->set_secret( self::get_unique_identifier() );
				$webhook->set_delivery_url( $delivery_url );
				$webhook->set_status( 'active' );
				$webhook->save();
				update_option( $val['option_name'], $webhook->get_id(), false );
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
		) {
			$identifier = $payload['order_key'];
			$payload    = [
				'platform_order_id'   => $payload['id'],
				'status'              => $payload['status'],
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

	private static function log_to_db( $type, $identifier, $data ) {
		global $wpdb;

		return $wpdb->insert( "{$wpdb->prefix}shop1_dropshipping_log", [
			'user_id'    => get_current_user_id(),
			'type'       => $type,
			'identifier' => $identifier,
			'payload'    => maybe_serialize( $data ),
		] );
	}

	public static function get_unique_identifier() {
		return uniqid( wp_rand( 10000, 99999 ) );
	}

	public static function get_shop1_connect_url() {
		$identifier = self::get_unique_identifier();
		update_option( self::SHOP1_CONNECT_NONCE_OPTION, $identifier, false );

		return add_query_arg( [
			'shop_name'           => get_bloginfo( 'name' ),
			'platform'            => 'WordPress/WooCommerce',
			'platform_url'        => home_url(),
			'scopes'              => 'read,write',
			'redirect_url'        => admin_url( 'admin-post.php?action=shop1-connect-response' ),
			'state'               => $identifier,
			'product_webhook_url' => admin_url( 'admin-ajax.php?action=shop1-product-hook' ),
			'order_webhook_url'   => admin_url( 'admin-ajax.php?action=shop1-order-hook' ),
		], 'https://admin.shop1.com/stores/third-party/connect' );
	}

	public static function shop1_connect_response() {
		if ( isset( $_GET['api_key'], $_GET['state'], $_GET['user_email'] )
		     && get_option( self::SHOP1_CONNECT_NONCE_OPTION )
		        === ( $identifier = sanitize_text_field( $_GET['state'] ) )
		) {
			$data = [
				'api_key'    => sanitize_text_field( $_GET['api_key'] ),
				'user_email' => sanitize_email( $_GET['user_email'] ),
				'identifier' => $identifier,
			];
			update_option( self::SHOP1_API_KEY_OPTION, $data, false );
			self::create_wc_order_webhooks();
			self::log_to_db( 'shop1_connect_response', $identifier, $data );
			?>
            <script>
                window.opener.location.reload();
                window.close();
            </script>
			<?php
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
		self::schedule_delete_all_shop1_products();
		self::remove_api_key_data();
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
					'message' => 'Disconnected successfully.',
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
						'message' => 'No matching record found.',
					] );
				}
			}
		}
		wp_send_json_error();
	}

	public static function shop1_order_hook() {
		$body   = self::parse_and_verify_json_body();
		$errors = [];
		if ( is_wp_error( $body ) ) {
			array_push( $errors, $body );
			wp_send_json_error( $errors );
		}

		if ( isset( $body['order']['order_id'], $body['order']['status'], $body['order'] ) ) {
			$wc_order = wc_get_order( $body['order']['order_id'] );
			if ( $wc_order ) {
				if ( isset( $body['order']['shipping_details']['tracking_number'] ) ) {
					if ( function_exists( 'wc_st_add_tracking_number' ) ) {
						wc_st_add_tracking_number(
							$wc_order->get_id(),
							$body['order']['shipping_details']['tracking_number'],
							$body['order']['shipping_details']['carrier'],
							$body['order']['shipping_details']['shipping_date']
						);
					} else {
						$wc_order->add_order_note(
							"Shop1 added tracking number:" . PHP_EOL .
							"Tracking Number: {$body['order']['shipping_details']['tracking_number']}" . PHP_EOL .
							"Carrier: {$body['order']['shipping_details']['carrier']}" . PHP_EOL .
							"Shipping Date: {$body['order']['shipping_details']['shipping_date']}"
						);
					}
				}
				$updated = $wc_order->update_status(
					$body['order']['status'],
					__( 'Order status updated by Shop1 webhook', 'shop1-dropshipping' ),
					true
				);
				if ( $updated ) {
					$wc_order->save();
					wp_send_json_success();
				} else {
					array_push( $errors, new \WP_Error(
						'order_status_update_failed',
						"Order couldn't be updated to status: ${$body['order']['status']}."
					) );
				}
			} else {
				array_push( $errors, new \WP_Error(
					'order_not_found',
					"WC order with id {$body['order']['order_id']} couldn't be found."
				) );
			}
		} else {
			array_push( $errors, new \WP_Error( 'invalid_body', 'Missing or invalid body.' ) );
		}
		wp_send_json_error( $errors );
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

				return new \WP_Error( 'authentication_failed', 'Failed to verify the authenticity of the request.' );
			}
		}

		return new \WP_Error( 'invalid_json', 'Missing or invalid JSON body.' );
	}

	private static function insert_products( $products, $is_update = false ) {
		$errors               = [];
		$inserted_product_ids = [];
		foreach ( $products as $product ) {
			if ( $is_update ) {
				$wc_product = wc_get_product( wc_get_product_id_by_sku( $product['SKU'] ) );
				if ( ! is_a( $wc_product, \WC_Product::class ) ) {
					array_push( $errors, new \WP_Error(
						'product_not_found',
						"Product with SKU: {$product['SKU']} couldn't be found."
					) );
					continue;
				}
			} else {
				$wc_product = new \WC_Product_Simple();
				try {
					$wc_product->set_sku( $product['SKU'] );
				} catch ( \WC_Data_Exception $e ) {
					array_push( $errors, $e->getMessage() );
					continue;
				}
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
			$images        = [];
			$wc_product_id = $wc_product->get_id();
			foreach ( $product['Images'] as $image ) {
				$image_attachment = new ImageAttachment( 0, $wc_product_id );
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
			if ( ! $is_update ) {
				array_push( $inserted_product_ids, $wc_product_id );
			}
			self::log_to_db( $is_update ? 'shop1_update_product' : 'shop1_add_product', $wc_product_id, $product );
		}
		if ( ! empty( $inserted_product_ids ) ) {
			self::insert_shop1_product_ids( $inserted_product_ids );
		}

		return $errors;
	}

	private static function get_shop1_product_ids() {
		return (array) get_option( self::SHOP1_PRODUCT_IDS_OPTION, [] );
	}

	private static function insert_shop1_product_ids( $product_ids ) {
		return update_option(
			self::SHOP1_PRODUCT_IDS_OPTION,
			array_keys(
				array_fill_keys( self::get_shop1_product_ids(), true ) +
				array_fill_keys( $product_ids, true )
			),
			false
		);
	}

	private static function remove_shop1_product_ids( $product_ids ) {
		return update_option(
			self::SHOP1_PRODUCT_IDS_OPTION,
			array_diff( self::get_shop1_product_ids(), $product_ids ),
			false
		);
	}

	private static function schedule_delete_all_shop1_products() {
		$product_ids = self::get_shop1_product_ids();
		for ( $i = 0; $i < ceil( count( $product_ids ) / self::BATCH_PROCESSING_LIMIT ); $i ++ ) {
			$current_batch_ids = array_slice( $product_ids, $i * self::BATCH_PROCESSING_LIMIT, self::BATCH_PROCESSING_LIMIT );
			wp_schedule_single_event( time() + $i * 30, 'schedule_remove_shop1_products', [ $current_batch_ids, $i ] );
		}
		delete_option( self::SHOP1_PRODUCT_IDS_OPTION );
	}

	public static function schedule_remove_shop1_products( $ids, $batch_number = 0 ) {
		self::remove_shop1_products( $ids, false, false );
	}

	private static function remove_shop1_products( $ids, $by_sku = false, $remove_ids = true ) {
		$product_ids = [];
		if ( $by_sku ) {
			foreach ( $ids as $sku ) {
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( is_int( $product_id ) && $product_id > 0 ) {
					array_push( $product_ids, $product_id );
				}
			}
		} else {
			$product_ids = $ids;
		}
		$removed_product_ids = [];
		foreach ( $product_ids as $product_id ) {
			$wc_product = wc_get_product( $product_id );
			if ( is_a( $wc_product, \WC_Product::class ) ) {
				$wc_product->delete( true );
				array_push( $removed_product_ids, $wc_product->get_id() );
			}
		}
		self::log_to_db( 'shop1_remove_products', 'rp-' . count( $ids ) . '-' . time(), $product_ids );
		if ( $remove_ids ) {
			self::remove_shop1_product_ids( $removed_product_ids );
		}
	}

	public static function shop1_product_hook() {
		$body   = self::parse_and_verify_json_body();
		$errors = [];
		if ( is_wp_error( $body ) ) {
			array_push( $errors, $body );
			wp_send_json_error( $errors );
		}
		if ( count( $body['products'] ) > self::BATCH_PROCESSING_LIMIT ) {
			array_push( $errors, new \WP_Error(
					'batch_limit_exceeded',
					'More than ' . self::BATCH_PROCESSING_LIMIT . ' products are not allowed.' )
			);
			wp_send_json_error( $errors );
		}
		if ( 'addProduct' === $body['action'] ) {
			array_merge( $errors, self::insert_products( $body['products'] ) );
		} elseif ( 'updateProduct' === $body['action'] ) {
			array_merge( $errors, self::insert_products( $body['products'], true ) );
		} elseif ( 'removeProduct' === $body['action'] ) {
			self::remove_shop1_products( $body['products'], true );
		}
		if ( empty( $errors ) ) {
			wp_send_json_success();
		}
		wp_send_json_error( $errors );
	}
}