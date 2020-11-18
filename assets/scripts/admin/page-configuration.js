jQuery(document).ready(function ($) {
    /*$('.wc-shop1-configuration-container .testing').on('load', function (e) {

    });*/
    const $container = $('.wc-shop1-configuration-container');
    const $testConnection = $container.find('.testing');
    if ($testConnection.length) {
        $.get(window.ajaxurl, {
            action: 'shop1-test-connection',
        }, null, 'json').done(function (data) {
            if (data.success) {
                if (data.data.code === 'not_authenticated') {
                    $testConnection.hide();
                    $container.find('.not-connected').show();
                } else if (data.data.code === 'verified_successfully') {
                    $testConnection.hide();
                    $container.find('.connected').show().find('.email').text(data.data.user_email);
                }
            }
        }).always(function () {
            $testConnection.find('.spinner').removeClass('is-active');
        });
    }
});