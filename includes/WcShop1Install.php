<?php


namespace WcShop1;


use WcShop1\Admin\Admin;

class WcShop1Install {
	const DB_VERSION = '0.0.5';

	const ACTIVATION_TRANSIENT = 'wc-shop1_activating';
	const DB_VERSION_OPTION = 'wc-shop1_db_version';

	public static function activate() {
		if ( 'yes' === get_transient( self::ACTIVATION_TRANSIENT ) ) {
			return;
		}

		set_transient( self::ACTIVATION_TRANSIENT, 'yes', MINUTE_IN_SECONDS * 10 );
		self::create_tables();
		delete_transient( self::ACTIVATION_TRANSIENT );
	}

	public static function uninstall() {
		Admin::cleanup_on_disconnect();
		delete_option( self::DB_VERSION_OPTION );
	}

	private static function create_tables() {
		$db_version = get_option( self::DB_VERSION_OPTION );

		if ( empty( $db_version ) || version_compare( $db_version, self::DB_VERSION, '<' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( self::get_schema() );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
		}
	}

	private static function get_schema() {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$tables = "
CREATE TABLE {$wpdb->prefix}wc_shop1_log (
	id bigint unsigned NOT NULL auto_increment,
	user_id bigint NOT NULL default 0,
	type varchar(64) NOT NULL,
	identifier varchar(64) NOT NULL,
	payload text,
	created_at timestamp,
	PRIMARY KEY  (id),
	KEY identifier (identifier),
	KEY user_id (user_id)
) $collate;";

		return $tables;
	}
}