<?php
/**
 * Dynamically load the classes attempting to use elsewhere in plugin.
 *
 * @param string $class Fully qualified name of class to load.
 */
spl_autoload_register( function ( $class ) {
	$prefix   = 'Shop1Dropshipping\\';
	$base_dir = dirname( SHOP1_DROPSHIPPING_PLUGIN_FILE ) . '/includes/';
	// Does the class use the namespace prefix?
	$prefix_len = strlen( $prefix );
	if ( 0 !== strncmp( $prefix, $class, $prefix_len ) ) {
		return;
	}
	// Get the relative class name
	$sep_pos   = strripos( $class, '\\' ) + 1;
	$rel_class = substr( $class, $prefix_len, $sep_pos - $prefix_len ) . substr( $class, $sep_pos ) . '.php';
	$file      = $base_dir . str_replace( array( '\\', '_' ), array( DIRECTORY_SEPARATOR, '-' ), $rel_class );
	// Require file.
	if ( stream_resolve_include_path( $file ) ) {
		require_once $file;
	}
} );