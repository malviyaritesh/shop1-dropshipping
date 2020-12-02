jQuery(document).ready(function ($) {
    const $container = $('.shop1-dropshipping-configurations-container');
    const $testConnection = $container.find('.testing');

    if ($testConnection.length) {
        $.get(window.ajaxurl, {
            action: 'shop1-test-connection',
        }, null, 'json').done(function (data) {
            if (data.success) {
                if (data.data.code === 'not_authenticated' || data.data.code === 'not_found') {
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

    $container.find('.not-connected button.connect').on('click', function (e) {
        const parentWindowWidth = $(window).width();
        const parentWindowHeight = $(window).height();
        const width = 800;
        const height = 600;
        const left = window.screenX + (parentWindowWidth > width ? (parentWindowWidth - width) / 2 : 0);
        const top = window.screenY + (parentWindowHeight > height ? (parentWindowHeight - height) / 2 : 0);
        window.open(
            $(this).data('url'),
            '_blank',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top +
            ',location=yes,status=yes,scrollable=yes,resizable=yes'
        );
    });

    $container.find('.connected button.disconnect').on('click', function (e) {
        const disconnectMessage = 'Disconnecting from Shop1 will schedule to remove products from your WC store' +
            " automatically which were added by Shop1. Are you sure you'd like to disconnect your Shop1 account?"
        if (confirm(disconnectMessage)) {
            $(this).text('Disconnecting...').prop('disabled', true);
            $.get(window.ajaxurl, {
                action: 'shop1-disconnect',
            }, null, 'json').done(function (data) {
                if (data && data.success && data.data.code === 'disconnected') {
                    window.location.reload();
                }
            });
        }
        return false;
    });
});