<?php

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( "You're not allowed to access this page." );
}

$shop1_logo = plugins_url( '/assets/images/shop1-logo.png', WC_SHOP1_PLUGIN_FILE );
?>

<div class="wrap wc-shop1-configuration-container">
    <img class="shop1-logo" src="<?php echo $shop1_logo; ?>" alt="shop1 logo"
         width="120">
    <section class="not-connected" style="display: none;">
        <p>
			<?php
			_e( "Let’s connect to your Shop1 account and import the drop shipping catalog " .
			    "into your website. This should only take a minute!", 'wc-shop1' );
			?>
        </p>
		<?php
		$shop1_connect_url = add_query_arg( [
			'action' => 'connect-to-shop1',
			'_nonce' => wp_create_nonce( 'connect-to-shop1' ),
		], admin_url( 'admin-post.php' ) );
		?>
        <a href="<?php echo $shop1_connect_url; ?>"
           class="button button-primary">
			<?php _e( 'Connect to Shop1', 'wc-shop1' ); ?>
        </a>
    </section>
    <section class="testing">
        <span class="spinner is-active"></span>
        <p>
			<?php _e( 'Testing connection to Shop1 servers...', 'wc-shop1' ); ?>
        </p>
        <p class="error" style="display: none;">
			<?php _e( 'Failed to test the connection.' ); ?>
            <a href="<?php echo admin_url( 'admin.php?page=' . \WcShop1\Admin\Admin::CONFIGURATIONS_SUBMENU_SLUG ); ?>">
				<?php _e( 'Reload', 'wc-shop1' ); ?>
            </a>
        </p>
    </section>
    <section class="connected" style="display: none;">
        <p>
			<?php _e( 'Your website is connected to your Shop1 account' ); ?>
            &lpar;<strong class="email"></strong>&rpar;
        </p>
        <button class="button button-secondary disconnect">
			<?php _e( 'Disconnect', 'wc-shop1' ); ?>
        </button>
    </section>
</div>
<style>
    .shop1-logo {
        margin-bottom: 1em;
    }

    .testing {
        display: flex;
        align-items: center;
    }

    .testing .spinner {
        margin: 0;
    }

    .testing p {
        margin-left: 1em;
    }

    .wc-shop1-configuration-container p.error {
        color: #aa0000;
    }
</style>
