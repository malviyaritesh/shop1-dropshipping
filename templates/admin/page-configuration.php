<?php

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( "You're not allowed to access this page." );
}

$shop1_logo = plugins_url( '/assets/images/shop1-logo.png', WC_SHOP1_PLUGIN_FILE );
?>

<div class="wrap">
    <img src="<?php echo $shop1_logo; ?>" alt="shop1 logo" width="140">
    <p>
        Let’s connect to your Shop1 account and import the dropshipping catalog
        into your website. This should only take a minute!
    </p>
	<?php
	$shop1_connect_url = add_query_arg( [
		'action' => 'connect-to-shop1',
		'_nonce' => wp_create_nonce( 'connect-to-shop1' ),
	], admin_url( 'admin-post.php' ) );
	?>
    <a href="<?php echo $shop1_connect_url; ?>" class="button button-primary">
        Connect to Shop1
    </a>
</div>
