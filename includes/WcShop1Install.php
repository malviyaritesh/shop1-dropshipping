<?php


namespace WcShop1;


class WcShop1Install {
	const DB_VERSION = '0.0.1';

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
	id bigint PRIMARY KEY,
	type varchar(255) NOT NULL,
	message text,
	created timestamp
);		";

		return $tables;
	}
}