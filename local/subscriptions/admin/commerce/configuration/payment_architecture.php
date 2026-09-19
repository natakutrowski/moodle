<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentArchitectureInspector;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\provider\CommercePaymentArchitectureCertificationService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\currency\CommerceCurrencyLabelFormatter;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
);
$title = get_string(
    'commerce_payment_architecture_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-payment-architecture-page'
);

$registry = CommerceRuntimeFactory::create()->payment_providers();
$currencyregistry = new CommerceCurrencyRegistry();
$inspector = new CommercePaymentArchitectureInspector($registry);
$certification = (
    new CommercePaymentArchitectureCertificationService(
        $registry,
        $currencyregistry
    )
)->certify();
$providers = $inspector->providers();
$currencies = $currencyregistry->enabled();
$renderissue = static function(
    array $issue
) use ($certification): string {
    return match ((string)($issue['code'] ?? '')) {
        'enabled_currency_without_provider' =>
            get_string(
                'commerce_payment_architecture_warning_currency_without_provider',
                'local_subscriptions',
                implode(
                    ', ',
                    $certification['uncoveredcurrencies']
                )
            ),
        default => (string)($issue['message'] ?? ''),
    };
};

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
        new moodle_url(
            '/local/subscriptions/admin/commerce/configuration/section.php',
            ['section' => 'payments']
        ),
        '← ' . get_string(
            'commerce_payment_architecture_back',
            'local_subscriptions'
        ),
        ['class' => 'btn btn-outline-secondary btn-sm']
    ),
    'mb-3'
);

echo html_writer::tag('h1', $title, ['class' => 'h3']);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_payment_architecture_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted mb-4']
);

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/configuration/section.php',
            ['section' => 'payments']
        ),
        get_string(
            'commerce_provider_ops_open_hub',
            'local_subscriptions'
        ),
        ['class' => 'btn btn-outline-primary btn-sm']
    ),
    'mb-4'
);


echo html_writer::start_div(
    'card mb-4 '
    . (
        $certification['certified']
            ? 'border-success'
            : 'border-danger'
    )
);
echo html_writer::start_div('card-body');
echo html_writer::start_div(
    'd-flex flex-wrap justify-content-between align-items-start gap-3 mb-3'
);
echo html_writer::div(
    html_writer::tag(
        'h2',
        '✅ ' . get_string(
            'commerce_payment_architecture_certification_title',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_payment_architecture_certification_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    )
);
echo html_writer::span(
    get_string(
        $certification['certified']
            ? 'commerce_payment_architecture_certified'
            : 'commerce_payment_architecture_not_certified',
        'local_subscriptions'
    ),
    'badge rounded-pill '
        . (
            $certification['certified']
                ? 'text-bg-success'
                : 'text-bg-danger'
        )
);
echo html_writer::end_div();

if ($certification['errors'] === []) {
    echo html_writer::div(
        get_string(
            'commerce_payment_architecture_no_errors',
            'local_subscriptions'
        ),
        'alert alert-success py-2'
    );
} else {
    $items = '';
    foreach ($certification['errors'] as $issue) {
        $items .= html_writer::tag(
            'li',
            s($renderissue($issue))
        );
    }
    echo html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'commerce_payment_architecture_errors',
                'local_subscriptions'
            )
        )
        . html_writer::tag('ul', $items, ['class' => 'mb-0 mt-2']),
        'alert alert-danger py-2'
    );
}

if ($certification['warnings'] !== []) {
    $items = '';
    foreach ($certification['warnings'] as $issue) {
        $items .= html_writer::tag(
            'li',
            s($renderissue($issue))
        );
    }
    echo html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'commerce_payment_architecture_warnings',
                'local_subscriptions'
            )
        )
        . html_writer::tag('ul', $items, ['class' => 'mb-0 mt-2']),
        'alert alert-warning py-2 mb-0'
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_payment_architecture_providers',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);

$providertable = new html_table();
$providertable->attributes['class'] = 'table table-sm align-middle';
$providertable->head = [
    get_string('commerce_payment_architecture_provider', 'local_subscriptions'),
    get_string('commerce_payment_architecture_available', 'local_subscriptions'),
    get_string('commerce_payment_architecture_admin_allowed', 'local_subscriptions'),
    get_string('commerce_payment_architecture_methods', 'local_subscriptions'),
    get_string('commerce_payment_architecture_currencies', 'local_subscriptions'),
    get_string('commerce_payment_architecture_capabilities', 'local_subscriptions'),
];

foreach ($providers as $provider) {
    $capabilities = [];
    foreach ([
        'redirect' => 'commerce_payment_capability_redirect',
        'retrieval' => 'commerce_payment_capability_retrieval',
        'cancellation' => 'commerce_payment_capability_cancellation',
        'refunds' => 'commerce_payment_capability_refund',
    ] as $key => $stringkey) {
        if (!empty($provider[$key])) {
            $capabilities[] = get_string(
                $stringkey,
                'local_subscriptions'
            );
        }
    }

    if (!empty($provider['refunds']) && empty($provider['refundcontract'])) {
        $capabilities[] = get_string(
            'commerce_payment_capability_refund_uncertified',
            'local_subscriptions'
        );
    }

    $providerkey = (string)$provider['key'];
    $providerlabelkey = 'provider_' . $providerkey;
    $providerlabel = get_string_manager()->string_exists(
        $providerlabelkey,
        'local_subscriptions'
    )
        ? get_string($providerlabelkey, 'local_subscriptions')
        : ucfirst($providerkey);

    $providericonpath =
        $CFG->dirroot
        . '/local/subscriptions/pix/providers/'
        . $providerkey
        . '.svg';

    $providericon = is_file($providericonpath)
        ? new moodle_url(
            '/local/subscriptions/pix/providers/'
            . $providerkey
            . '.svg'
        )
        : null;

    $methodlabels = [];
    foreach ((array)$provider['paymentmethods'] as $method) {
        $methodkey = 'commerce_payment_method_' . $method;
        $methodlabels[] = get_string_manager()->string_exists(
            $methodkey,
            'local_subscriptions'
        )
            ? get_string($methodkey, 'local_subscriptions')
            : (string)$method;
    }

    $providertable->data[] = [
        (
            $providericon === null
                ? ''
                : html_writer::empty_tag('img', [
                    'src' => $providericon->out(false),
                    'alt' => '',
                    'class' =>
                        'commerce-config-provider-icon me-2',
                ])
        )
        . html_writer::span(
            s($providerlabel),
            'commerce-config-provider-name'
        ),
        get_string(
            !empty($provider['available']) ? 'yes' : 'no'
        ),
        html_writer::span(
            get_string(
                !empty($provider['adminallowed'])
                    ? 'yes'
                    : 'no'
            ),
            !empty($provider['adminallowed'])
                ? 'badge text-bg-success'
                : 'badge text-bg-secondary'
        ),
        s(implode(', ', $methodlabels)),
        s(implode(', ', (array)$provider['currencies'])),
        $capabilities !== []
            ? s(implode(' · ', $capabilities))
            : get_string(
                'commerce_payment_capability_none',
                'local_subscriptions'
            ),
    ];
}
echo html_writer::table($providertable);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'h2',
    '↩️ ' . get_string(
        'commerce_payment_refund_readiness_title',
        'local_subscriptions'
    ),
    ['class' => 'h5 mb-2']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_payment_refund_readiness_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted mb-3']
);

$refundtable = new html_table();
$refundtable->attributes['class'] = 'table table-sm align-middle mb-0';
$refundtable->head = [
    get_string(
        'commerce_payment_architecture_provider',
        'local_subscriptions'
    ),
    get_string(
        'commerce_payment_refund_capability_column',
        'local_subscriptions'
    ),
    get_string(
        'commerce_payment_refund_contract_column',
        'local_subscriptions'
    ),
    get_string(
        'commerce_payment_refund_certified_column',
        'local_subscriptions'
    ),
    get_string(
        'commerce_payment_refund_history_column',
        'local_subscriptions'
    ),
];

foreach ($providers as $provider) {
    $providerkey = (string)$provider['key'];
    $providerlabelkey = 'provider_' . $providerkey;
    $providerlabel = get_string_manager()->string_exists(
        $providerlabelkey,
        'local_subscriptions'
    )
        ? get_string($providerlabelkey, 'local_subscriptions')
        : ucfirst($providerkey);

    $refundtable->data[] = [
        (
            is_file(
                $CFG->dirroot
                . '/local/subscriptions/pix/providers/'
                . $providerkey
                . '.svg'
            )
                ? html_writer::empty_tag('img', [
                    'src' => (
                        new moodle_url(
                            '/local/subscriptions/pix/providers/'
                            . $providerkey
                            . '.svg'
                        )
                    )->out(false),
                    'alt' => '',
                    'class' =>
                        'commerce-config-provider-icon me-2',
                ])
                : ''
        ) . s($providerlabel),
        get_string(
            !empty($provider['refunds']) ? 'yes' : 'no'
        ),
        get_string(
            !empty($provider['refundcontract']) ? 'yes' : 'no'
        ),
        html_writer::span(
            get_string(
                !empty($provider['refundcertified'])
                    ? 'commerce_payment_refund_certified_yes'
                    : 'commerce_payment_refund_certified_no',
                'local_subscriptions'
            ),
            'badge rounded-pill '
                . (
                    !empty($provider['refundcertified'])
                        ? 'text-bg-success'
                        : 'text-bg-secondary'
                )
        ),
        get_string(
            !empty($provider['refundhistorycontract'])
                ? 'yes'
                : 'no'
        ),
    ];
}
echo html_writer::table($refundtable);
echo html_writer::end_div();
echo html_writer::end_div();


echo html_writer::start_div('card mb-4 border-primary');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_payment_architecture_matrix',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_payment_architecture_matrix_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);

$methodiconmap = [
    'card' => [
        'file' => 'card.svg',
        'fallback' => 'fa-regular fa-credit-card',
    ],
    'apple_pay' => [
        'file' => 'applepay.svg',
        'fallback' => 'fa-brands fa-apple',
    ],
    'google_pay' => [
        'file' => 'googlepay.svg',
        'fallback' => 'fa-brands fa-google',
    ],
    'paypal' => [
        'file' => 'paypal.svg',
        'fallback' => 'fa-brands fa-paypal',
    ],
    'link' => [
        'file' => 'link.svg',
        'fallback' => 'fa-solid fa-link',
    ],
    'klarna' => [
        'file' => 'klarna.svg',
        'fallback' => 'fa-solid fa-money-bill-wave',
    ],
    'alfa_pay' => [
        'file' => 'alfapay.svg',
        'fallback' => 'fa-solid fa-bolt',
    ],
    'sbp' => [
        'file' => 'sbp.svg',
        'fallback' => 'fa-solid fa-qrcode',
    ],
    'sberpay' => [
        'file' => 'sberpay.svg',
        'fallback' => 'fa-solid fa-bolt',
    ],
    'mir_pay' => [
        'file' => 'mirpay.svg',
        'fallback' => 'fa-solid fa-wallet',
    ],
];

$methodheading = static function(
    string $methodkey
) use ($methodiconmap): string {
    global $CFG;

    $label = get_string(
        'commerce_payment_method_' . $methodkey,
        'local_subscriptions'
    );

    $icondef = $methodiconmap[$methodkey]
        ?? [
            'file' => '',
            'fallback' => 'fa-solid fa-wallet',
        ];

    $icon = '';

    if ($icondef['file'] !== '') {
        $path =
            $CFG->dirroot
            . '/local/subscriptions/pix/providers/'
            . $icondef['file'];

        if (is_file($path)) {
            $icon =
                html_writer::empty_tag(
                    'img',
                    [
                        'src' => (
                            new moodle_url(
                                '/local/subscriptions/pix/providers/'
                                . $icondef['file']
                            )
                        )->out(false),
                        'alt' => '',
                        'class' =>
                            'commerce-payment-method-matrix-icon',
                    ]
                );
        }
    }

    if ($icon === '') {
        $icon =
            html_writer::tag(
                'i',
                '',
                [
                    'class' =>
                        $icondef['fallback']
                        . ' commerce-payment-method-matrix-icon-fallback',
                    'aria-hidden' => 'true',
                ]
            );
    }

    return html_writer::span(
        $icon
        . html_writer::span(
            s($label),
            'commerce-payment-method-matrix-label'
        ),
        'commerce-payment-method-matrix-heading'
    );
};

$matrixtable = new html_table();
$matrixtable->attributes['class'] = 'table table-sm align-middle';
$matrixtable->head = [
    get_string('currency'),
];

foreach (
    CommercePaymentMethodCatalogue::keys()
    as $methodkey
) {
    $matrixtable->head[] =
        $methodheading(
            $methodkey
        );
}

foreach ($currencies as $currency) {
    $methods = $inspector->methods($currency);
    $bykey = [];
    foreach ($methods as $method) {
        $bykey[(string)$method['method']] = $method;
    }

    $row = [
        CommerceCurrencyLabelFormatter::format($currency),
    ];

    foreach (CommercePaymentMethodCatalogue::keys() as $methodkey) {
        $method = $bykey[$methodkey] ?? null;
        if (
            $method !== null
            && empty($method['adminallowed'])
        ) {
            $row[] = html_writer::span(
                get_string(
                    'commerce_payment_architecture_admin_disabled',
                    'local_subscriptions'
                ),
                'badge text-bg-secondary'
            );
            continue;
        }

        if (
            $method === null
            || empty($method['available'])
        ) {
            $row[] = '—';
            continue;
        }

        $providerlabels = [];
        foreach ((array)$method['providers'] as $providerkey) {
            $providerlabelkey = 'provider_' . $providerkey;
            $providerlabel = get_string_manager()->string_exists(
                $providerlabelkey,
                'local_subscriptions'
            )
                ? get_string($providerlabelkey, 'local_subscriptions')
                : ucfirst((string)$providerkey);

            $providericonpath =
                $CFG->dirroot
                . '/local/subscriptions/pix/providers/'
                . $providerkey
                . '.svg';

            $providerlabels[] =
                (
                    is_file($providericonpath)
                        ? html_writer::empty_tag('img', [
                            'src' => (
                                new moodle_url(
                                    '/local/subscriptions/pix/providers/'
                                    . $providerkey
                                    . '.svg'
                                )
                            )->out(false),
                            'alt' => '',
                            'class' =>
                                'commerce-config-provider-icon me-1',
                        ])
                        : ''
                )
                . s($providerlabel)
                . (
                    !empty($method['marketdependent'])
                        ? html_writer::span(
                            '*',
                            'text-muted ms-1',
                            [
                                'title' => get_string(
                                    'commerce_payment_architecture_market_dependent',
                                    'local_subscriptions'
                                ),
                            ]
                        )
                        : ''
                );
        }
        $row[] = implode('<br>', $providerlabels);
    }

    $matrixtable->data[] = $row;
}
echo html_writer::table($matrixtable);

echo html_writer::div(
    get_string(
        'commerce_payment_architecture_market_note',
        'local_subscriptions'
    ),
    'small text-muted mt-2'
);

echo html_writer::div(
    get_string(
        'commerce_payment_architecture_note',
        'local_subscriptions'
    ),
    'alert alert-info mt-3 mb-0'
);

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
