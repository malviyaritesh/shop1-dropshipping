jQuery(document).ready(function ($) {
    const $container = $('.wc-shop1-configuration-container');
    const $testConnection = $container.find('.testing');

    if ($testConnection.length) {
        $.get(window.ajaxurl, {
            action: 'shop1-test-connection',
            XDEBUG_SESSION_START: 1,
        }, null, 'json').done(function (data) {
            if (data.success) {
                if (data.data.code === 'not_authenticated') {
                    $testConnection.hide();
                    $container.find('.not-connected').show();
                } else if (data.data.code === 'verified_successfully') {
                    $testConnection.hide();
                    $container.find('.connected').show().find('.email').text(data.data.user_email);
                }
            } else {
                $testConnection.find('p:not(.error)').hide();
                $testConnection.find('p.error').show();
            }
        }).fail(function () {
            $testConnection.find('p:not(.error)').hide();
            $testConnection.find('p.error').show();
        }).always(function () {
            $testConnection.find('.spinner').removeClass('is-active');
        });
    }

    $container.find('.connected button.disconnect').on('click', function (e) {
        if (confirm("Are you sure you'd like to disconnect your Shop1 account?")) {
            $(this).text('Disconnecting...').prop('disabled', true);
            $.get(window.ajaxurl, {
                action: 'shop1-disconnect',
            }, null, 'json').done(function (data) {
                if (data && data.success && data.data.code === 'disconnected') {
                    window.location.reload();
                }
            }).fail(function () {
                console.error('This failed miserably');
            });
        }
        return false;
    });
});