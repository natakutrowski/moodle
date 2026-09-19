<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderOperationalStatusService;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderConnectionTestService;
use local_subscriptions\commerce\payment\provider\paypal\PayPalOperationalHealthService;
use local_subscriptions\crm\commerce\rendering\CommercePaymentProviderOperationalRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\payment\Provider;

$context =
    AdminSecurity::require(
        Capabilities::MANAGE_CONFIGURATION
    );

$provider = required_param(
    'provider',
    PARAM_ALPHANUMEXT
);

if (
    !in_array(
        $provider,
        [
            Provider::STRIPE,
            Provider::ALFA,
            Provider::PAYPAL,
        ],
        true
    )
) {
    throw new moodle_exception(
        'invalidparameter'
    );
}

$checkremote = optional_param(
    'checkremote',
    0,
    PARAM_BOOL
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/configuration/provider.php',
    ['provider' => $provider]
);

$huburl = new moodle_url(
    '/local/subscriptions/admin/commerce/configuration/section.php',
    ['section' => 'payments']
);

$architectureurl = new moodle_url(
    '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
);

$label =
    CommercePaymentProviderOperationalRenderer::label(
        $provider
    );

$title = get_string(
    'commerce_provider_ops_title',
    'local_subscriptions',
    $label
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-provider-ops-page'
);

$statusservice =
    new CommercePaymentProviderOperationalStatusService();

$status =
    $statusservice->get(
        $provider
    );

$connectionresult = null;
if ((bool)$checkremote) {
    try {
        (new CommercePaymentProviderConnectionTestService())->test($provider);
        $connectionresult = ['ok' => true, 'message' => ''];
    } catch (\Throwable $exception) {
        $connectionresult = ['ok' => false, 'message' => $exception->getMessage()];
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::CONFIGURATION,
    $context
);

echo html_writer::div(
    html_writer::link(
        $huburl,
        '← ' . get_string(
            'commerce_provider_ops_back_payments',
            'local_subscriptions'
        ),
        [
            'class' =>
                'btn btn-outline-secondary btn-sm',
        ]
    ),
    'mb-3'
);

echo html_writer::div(
    CommercePaymentProviderOperationalRenderer::icon(
        $provider
    )
    . html_writer::tag(
        'h1',
        s($title),
        ['class' => 'h3 mb-0']
    ),
    'd-flex align-items-center gap-3 mb-2'
);

echo html_writer::tag(
    'p',
    get_string(
        'commerce_provider_ops_description',
        'local_subscriptions',
        $label
    ),
    ['class' => 'text-muted mb-4']
);

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_provider_ops_state_title',
        'local_subscriptions'
    ),
    ['class' => 'h5 mb-3']
);
echo CommercePaymentProviderOperationalRenderer::status_table(
    $status
);
echo html_writer::end_div();
echo html_writer::end_div();

if ($connectionresult !== null) {
    echo html_writer::div(
        $connectionresult['ok']
            ? get_string('commerce_provider_ops_remote_ok_generic', 'local_subscriptions', $label)
            : get_string('commerce_provider_ops_remote_failed_generic', 'local_subscriptions', (object)['provider' => $label, 'error' => $connectionresult['message']]),
        $connectionresult['ok'] ? 'alert alert-success' : 'alert alert-danger'
    );
}

echo html_writer::div(
    get_string(
        'commerce_provider_ops_credentials_hint',
        'local_subscriptions'
    ),
    'alert alert-light border'
);

echo html_writer::start_div(
    'd-flex flex-wrap gap-2'
);

echo html_writer::link(
    $huburl,
    get_string(
        'commerce_provider_ops_edit_configuration',
        'local_subscriptions'
    ),
    ['class' => 'btn btn-primary']
);

echo html_writer::link(
    $architectureurl,
    get_string(
        'commerce_provider_ops_open_architecture',
        'local_subscriptions'
    ),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::link(
    new moodle_url(
        $pageurl,
        ['checkremote' => 1]
    ),
    get_string(
        'commerce_provider_ops_test_connection',
        'local_subscriptions'
    ),
    ['class' => 'btn btn-outline-primary']
);

echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
