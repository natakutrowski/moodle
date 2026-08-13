<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter;
use local_subscriptions\payment\Provider;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$repository = new CommercePurchaseReadRepository($DB);
$purchase = $repository->find_by_id($id);
if ($purchase === null) { throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions'); }
$summary = $purchase->summary;
$alfareconciled = optional_param('alfa_reconciled', 0, PARAM_BOOL);
$publicreference = $summary->publicreference !== ''
    ? $summary->publicreference
    : (new CommercePublicOrderReference())->from_internal(
        $summary->reference,
        $summary->timecreated
    );
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $id]);
$pagetitle = get_string('commerce_purchase_view_title', 'local_subscriptions', $publicreference);
$orderdetailsurl = new moodle_url('/local/subscriptions/order_details.php', [
    'reference' => $summary->reference,
]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-purchase-view-page');
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/commerce_purchase_pricing.css'));

$definition = static function(array $rows): string {
    $html = html_writer::start_tag('dl', ['class' => 'row mb-0']);
    foreach ($rows as [$label, $value]) { $html .= html_writer::tag('dt', s($label), ['class' => 'col-sm-4 text-muted']) . html_writer::tag('dd', $value, ['class' => 'col-sm-8']); }
    return $html . html_writer::end_tag('dl');
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_purchases_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php')],
    ['label' => $publicreference, 'url' => null],
]);
echo CrmPageHeader::render($pagetitle, get_string('commerce_purchase_view_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PURCHASES);
if ($alfareconciled) {
    echo html_writer::div(
        s(get_string('commerce_alfa_crm_success', 'local_subscriptions')),
        'alert alert-success mt-3'
    );
}
$quickactions = html_writer::start_div('d-flex flex-wrap gap-2');
$quickactions .= html_writer::link(
    $orderdetailsurl,
    get_string('commerce_purchase_open_order_details', 'local_subscriptions'),
    [
        'class' => 'btn btn-primary',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]
);
$quickactions .= html_writer::link(
    new moodle_url('/local/subscriptions/order_invoice.php', [
        'reference' => $summary->reference,
    ]),
    get_string('commerce_purchase_download_invoice', 'local_subscriptions'),
    [
        'class' => 'btn btn-outline-primary',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]
);
$quickactions .= html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', [
        'purchaseid' => $id,
    ]),
    get_string('commerce_purchase_open_mail_journal', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);
if ($summary->provider === Provider::ALFA) {
    $quickactions .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php', ['id' => $id]),
        get_string('commerce_alfa_crm_verify', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    );
}
if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
    $resendreceipturl = new moodle_url(
        '/local/subscriptions/admin/commerce/purchases/resend_receipt.php',
        [
            'id' => $id,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]
    );
    $quickactions .= html_writer::link(
        $resendreceipturl,
        get_string('commerce_purchase_resend_receipt', 'local_subscriptions'),
        [
            'class' => 'btn btn-outline-warning',
            'data-confirmation' => 'modal',
            'data-confirmation-title-str' => json_encode([
                'commerce_purchase_resend_receipt',
                'local_subscriptions',
            ]),
            'data-confirmation-content-str' => json_encode([
                'commerce_purchase_resend_receipt_confirm',
                'local_subscriptions',
            ]),
            'data-confirmation-yes-button-str' => json_encode(['yes']),
            'data-confirmation-destination' => $resendreceipturl->out(false),
        ]
    );
}
if ($summary->customer->userid !== null || trim((string)$summary->customer->email) !== '') {
    $user360params = $summary->customer->userid !== null
        ? ['id' => $summary->customer->userid]
        : ['email' => (string)$summary->customer->email];
    $quickactions .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/users/view.php', $user360params),
        get_string('commerce_purchase_open_user360', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
}
$quickactions .= html_writer::end_div();
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_actions_section', 'local_subscriptions'),
    $quickactions,
    'mt-3'
);
echo CommerceDesignSystemRenderer::metrics([
    ['label' => get_string('commerce_purchase_amount', 'local_subscriptions'), 'value' => CommercePurchasePresentation::money($summary->totalminor, $summary->currency)],
    ['label' => get_string('commerce_purchase_commercial_status', 'local_subscriptions'), 'value' => CommercePurchasePresentation::commercial_status_label($summary->commercialstatus)],
    ['label' => get_string('commerce_purchase_items_count', 'local_subscriptions'), 'value' => count($purchase->items)],
]);

$statusdimensions = CommercePurchasePresentation::status_dimensions(
    $summary->totalminor,
    $summary->commercialstatus,
    $summary->paymentstatus,
    $summary->fulfillmentstatus
);
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_status_overview', 'local_subscriptions'),
    $definition(array_map(
        static fn(array $dimension): array => [$dimension['label'], $dimension['value']],
        $statusdimensions
    )),
    'mt-4'
);

if ($summary->provider === Provider::ALFA) {
    $pendingalfa = !in_array($summary->paymentstatus, ['paid', 'completed', 'succeeded'], true)
        || $summary->commercialstatus !== 'fulfilled';
    $alfacontent = html_writer::div(
        s(get_string(
            $pendingalfa ? 'commerce_alfa_crm_purchase_pending_help' : 'commerce_alfa_crm_purchase_complete_help',
            'local_subscriptions'
        )),
        'text-muted mb-3'
    );
    $alfacontent .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php', ['id' => $id]),
        get_string('commerce_alfa_crm_verify', 'local_subscriptions'),
        ['class' => $pendingalfa ? 'btn btn-primary' : 'btn btn-outline-primary']
    );
    echo CommerceDesignSystemRenderer::panel(
        get_string('commerce_alfa_crm_purchase_panel', 'local_subscriptions'),
        $alfacontent,
        'mt-4'
    );
}

echo html_writer::start_div('row g-4 mt-1');
echo html_writer::start_div('col-lg-6');
echo CommerceDesignSystemRenderer::panel(get_string('commerce_purchase_summary_section', 'local_subscriptions'), $definition([
    [
        get_string('commerce_purchase_public_reference', 'local_subscriptions'),
        html_writer::tag('code', s($publicreference), ['class' => 'fw-semibold'])
    ],
    [
        get_string('commerce_purchase_internal_reference', 'local_subscriptions'),
        html_writer::tag('code', s($summary->reference), ['class' => 'small'])
    ],
    [get_string('date'), s(userdate($summary->timecreated, get_string('strftimedatetimeshort', 'langconfig')))],
    [get_string('commerce_purchase_type', 'local_subscriptions'), CommercePurchasePresentation::type_badge($summary->type)],
    [get_string('commerce_purchase_status', 'local_subscriptions'), CommercePurchasePresentation::commercial_status_badge($summary->commercialstatus)],
    [get_string('commerce_purchase_provider', 'local_subscriptions'), $summary->provider === null ? '—' : Provider::label_with_icon($summary->provider)],
]));
echo html_writer::end_div();
echo html_writer::start_div('col-lg-6');
$customername = $summary->customer->display_name();
$customeractions = '';
if ($summary->customer->userid !== null || trim((string)$summary->customer->email) !== '') {
    $customeractions = html_writer::start_div('d-flex flex-wrap gap-2 mt-2');
    $user360params = $summary->customer->userid !== null
        ? ['id' => $summary->customer->userid]
        : ['email' => (string)$summary->customer->email];
    $customeractions .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/users/view.php', $user360params),
        get_string('commerce_purchase_open_user360', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );
    if ($summary->customer->userid !== null) {
        $customeractions .= html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $summary->customer->userid]),
            get_string('commerce_purchase_open_moodle_profile', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );
    }
    $customeractions .= html_writer::end_div();
}
echo CommerceDesignSystemRenderer::panel(get_string('commerce_purchase_customer_section', 'local_subscriptions'), $definition([
    [get_string('name'), s($customername !== '' ? $customername : '—')],
    [get_string('email'), s($summary->customer->email)],
    [get_string('commerce_purchase_identifier', 'local_subscriptions'), s((string)($summary->customer->userid ?? '—'))],
]) . $customeractions);
echo html_writer::end_div();
echo html_writer::end_div();

$pricingpresenter =
    new CommercePersistedCommercialPricingPresenter();
$itempricingmodels = [];
$itemtable = new html_table();
$itemtable->head = [
    get_string('commerce_purchase_product', 'local_subscriptions'),
    get_string('commerce_purchase_type', 'local_subscriptions'),
    get_string('commerce_purchase_quantity', 'local_subscriptions'),
    get_string('commerce_purchase_amount', 'local_subscriptions'),
];
$itemtable->attributes['class'] = 'generaltable table align-middle';
foreach ($purchase->items as $item) {
    $metadata = json_decode(
        (string)($item->metadatajson ?? ''),
        true
    );
    $metadata = is_array($metadata) ? $metadata : [];
    $pricing = $pricingpresenter->item(
        $metadata,
        (int)$item->grossminor,
        (int)$item->discountminor,
        (int)$item->netminor,
        (int)$item->quantity
    );
    $itempricingmodels[] = $pricing;

    $producthtml = html_writer::div(
        s((string)$item->label),
        'fw-semibold'
    ) . html_writer::tag(
        'code',
        s((string)$item->itemreference),
        ['class' => 'small']
    );
    if (trim((string)$item->itemreference) !== '') {
        $producthtml .= html_writer::div(
            html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/products/view.php',
                    ['sku' => (string)$item->itemreference]
                ),
                get_string(
                    'commerce_purchase_open_product',
                    'local_subscriptions'
                ),
                ['class' => 'small']
            ),
            'mt-1'
        );
    }

    if ($pricing['haspricing']) {
        $badges = '';
        if ($pricing['isupgrade']) {
            $badges .= html_writer::span(
                get_string('commerce_storefront_upgrade_offer_badge', 'local_subscriptions'),
                'commerce-crm-pricing-badge commerce-crm-pricing-badge--upgrade'
            );
        } else if ($pricing['hastrial']) {
            $badges .= html_writer::span(
                get_string('commerce_trial_storefront_badge', 'local_subscriptions'),
                'commerce-crm-pricing-badge commerce-crm-pricing-badge--trial'
            );
        }
        if ($pricing['hastrialpercent']) {
            $badges .= html_writer::span(
                get_string('commerce_trial_storefront_discount', 'local_subscriptions', (int)$pricing['trialpercent']),
                'commerce-crm-pricing-badge commerce-crm-pricing-badge--saving'
            );
        }
        if ($pricing['haspromotionpercent']) {
            $badges .= html_writer::span(
                '−' . (int)$pricing['promotionpercent'] . '%',
                'commerce-crm-pricing-badge commerce-crm-pricing-badge--promotion'
            );
        }

        $pricingrows = [[
            get_string('commerce_cart_list_total', 'local_subscriptions'),
            CommercePurchasePresentation::money((int)$pricing['initialminor'], (string)$item->currency),
        ]];
        if ($pricing['haspromotion']) {
            $pricingrows[] = [
                $pricing['haspromotionpercent']
                    ? get_string('commerce_pricing_initial_promotion_percent', 'local_subscriptions', (int)$pricing['promotionpercent'])
                    : get_string('commerce_pricing_initial_promotion', 'local_subscriptions'),
                '− ' . CommercePurchasePresentation::money((int)$pricing['promotionminor'], (string)$item->currency),
            ];
        }
        if ($pricing['hastrial']) {
            $pricingrows[] = [
                get_string('commerce_cart_trial_discount_total', 'local_subscriptions'),
                '− ' . CommercePurchasePresentation::money((int)$pricing['trialminor'], (string)$item->currency),
            ];
        }
        if ($pricing['hascredit']) {
            $creditlabel = (string)$pricing['fromlabel'] !== ''
                ? get_string('commerce_pricing_owned_credit', 'local_subscriptions', (string)$pricing['fromlabel'])
                : get_string('commerce_cart_upgrade_credit_total', 'local_subscriptions');
            $pricingrows[] = [
                $creditlabel,
                '− ' . CommercePurchasePresentation::money((int)$pricing['creditminor'], (string)$item->currency),
            ];
        }
        if ($pricing['hasotherdiscount']) {
            $pricingrows[] = [
                get_string('commerce_invoice_other_discount', 'local_subscriptions'),
                '− ' . CommercePurchasePresentation::money((int)$pricing['otherdiscountminor'], (string)$item->currency),
            ];
        }
        $pricingrows[] = [
            get_string('commerce_invoice_total_paid', 'local_subscriptions'),
            html_writer::tag('strong', CommercePurchasePresentation::money((int)$pricing['finalminor'], (string)$item->currency)),
        ];

        $producthtml .= html_writer::div($badges, 'commerce-crm-pricing-badges');
        if ($pricing['hasupgradepath']) {
            $producthtml .= html_writer::div(
                s((string)$pricing['fromlabel']) . ' → ' . s((string)$pricing['tolabel']),
                'commerce-crm-pricing-path'
            );
        }
        $producthtml .= html_writer::tag(
            'details',
            html_writer::tag('summary', get_string('commerce_pricing_details', 'local_subscriptions'))
                . html_writer::div($definition($pricingrows), 'commerce-crm-pricing-details__body'),
            ['class' => 'commerce-crm-pricing-details']
        );
    }

    $itemtable->data[] = [
        $producthtml,
        CommercePurchasePresentation::type_badge(
            (string)$item->itemtype
        ),
        (int)$item->quantity,
        CommercePurchasePresentation::money(
            (int)$pricing['finalminor'],
            (string)$item->currency
        ),
    ];
}
echo CommerceDesignSystemRenderer::panel(get_string('commerce_purchase_products_section', 'local_subscriptions'), html_writer::table($itemtable), 'mt-4');

$orderpricing = $pricingpresenter->order(
    $purchase->metadata,
    $itempricingmodels,
    $summary->totalminor
);
$promotioncodes = array_values(array_filter(
    (array)($purchase->metadata['promotion_codes'] ?? []),
    'is_string'
));

if ($orderpricing['haspricing'] || $promotioncodes !== []) {
    $pricingrows = [
        [
            get_string(
                'commerce_cart_list_total',
                'local_subscriptions'
            ),
            CommercePurchasePresentation::money(
                (int)$orderpricing['initialminor'],
                $summary->currency
            ),
        ],
    ];

    foreach ([
        [
            'condition' => 'haspromotion',
            'label' => 'commerce_cart_product_promotions_total',
            'amount' => 'promotionminor',
        ],
        [
            'condition' => 'hastrial',
            'label' => 'commerce_cart_trial_discount_total',
            'amount' => 'trialminor',
        ],
        [
            'condition' => 'hascredit',
            'label' => 'commerce_cart_upgrade_credit_total',
            'amount' => 'creditminor',
        ],
        [
            'condition' => 'hasadjustment',
            'label' => 'commerce_invoice_other_discount',
            'amount' => 'adjustmentminor',
        ],
    ] as $row) {
        if (!$orderpricing[$row['condition']]) {
            continue;
        }
        $pricingrows[] = [
            get_string($row['label'], 'local_subscriptions'),
            '− ' . CommercePurchasePresentation::money(
                (int)$orderpricing[$row['amount']],
                $summary->currency
            ),
        ];
    }

    if ($promotioncodes !== []) {
        $pricingrows[] = [
            get_string(
                'commerce_i411_promo_code',
                'local_subscriptions'
            ),
            s(implode(', ', $promotioncodes)),
        ];
    }

    $pricingrows[] = [
        get_string(
            'commerce_cart_total_reductions',
            'local_subscriptions'
        ),
        '− ' . CommercePurchasePresentation::money(
            (int)$orderpricing['totalreductionminor'],
            $summary->currency
        ),
    ];
    $pricingrows[] = [
        get_string(
            'commerce_invoice_total_paid',
            'local_subscriptions'
        ),
        html_writer::tag(
            'strong',
            CommercePurchasePresentation::money(
                $summary->totalminor,
                $summary->currency
            )
        ),
    ];

    echo CommerceDesignSystemRenderer::panel(
        get_string(
            'commerce_purchase_pricing_section',
            'local_subscriptions'
        ),
        $definition($pricingrows),
        'mt-4'
    );
}

$paymenttable = new html_table();
$paymenttable->head = [
    get_string('commerce_purchase_status', 'local_subscriptions'),
    get_string('commerce_purchase_provider', 'local_subscriptions'),
    get_string('commerce_purchase_amount', 'local_subscriptions'),
    get_string('commerce_purchase_provider_reference', 'local_subscriptions'),
    get_string('date'),
    get_string('commerce_purchase_payment_request', 'local_subscriptions'),
];
$paymenttable->attributes['class'] = 'generaltable table align-middle';
foreach ($purchase->payments as $payment) {
    $providerhtml = $payment->provider === null ? '—' : Provider::label_with_icon($payment->provider);
    $requesthtml = get_string(
        'commerce_purchase_native_payment_attempt',
        'local_subscriptions'
    );
    if ($payment->paymentrequest !== null) {
        $request = $payment->paymentrequest;
        $requesthtml = html_writer::link(
            '#commerce-payment-request-' . $request->family . '-' . $request->id,
            get_string('commerce_purchase_payment_request_open', 'local_subscriptions', $request->id),
            ['class' => 'small fw-semibold']
        );
    }

    $paymenttable->data[] = [
        CommercePurchasePresentation::technical_status_badge('payment', $payment->status),
        $providerhtml,
        CommercePurchasePresentation::money($payment->amountminor, $payment->currency),
        html_writer::tag('code', s($payment->transactionid ?? $payment->providerreference ?? '—'), ['class' => 'small']),
        $payment->paidat === null
            ? '—'
            : s(userdate($payment->paidat, get_string('strftimedatetimeshort', 'langconfig'))),
        $requesthtml,
    ];
}
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_payments_section', 'local_subscriptions'),
    $purchase->payments === []
        ? html_writer::tag('p', get_string('commerce_purchase_no_payments', 'local_subscriptions'), ['class' => 'text-muted mb-0'])
        : html_writer::table($paymenttable),
    'mt-4'
);


$paymentrequests = [];
foreach ($purchase->payments as $payment) {
    if ($payment->paymentrequest !== null) {
        $paymentrequests[$payment->paymentrequest->family . ':' . $payment->paymentrequest->id] =
            $payment->paymentrequest;
    }
}
if ($paymentrequests !== []) {
    $requestblocks = '';
    foreach ($paymentrequests as $request) {
        $detailrows = [
            [get_string('commerce_purchase_payment_request_family', 'local_subscriptions'), s($request->family)],
            [get_string('commerce_purchase_status', 'local_subscriptions'), CommercePurchasePresentation::technical_status_badge('payment', $request->status)],
            [get_string('commerce_purchase_provider', 'local_subscriptions'), Provider::label_with_icon($request->provider)],
            [get_string('commerce_purchase_amount', 'local_subscriptions'), CommercePurchasePresentation::money($request->amountminor, $request->currency)],
        ];
        foreach ($request->details as $field => $value) {
            if (in_array($field, ['status', 'payment_provider', 'currency', 'amount_minor'], true)) {
                continue;
            }
            if ($value === null || $value === '') {
                $formatted = '—';
            } else if (in_array($field, ['creation_date', 'last_update', 'payment_date', 'expiration_date', 'last_attempt', 'locked_at', 'retry_expires', 'reminder1_at', 'reminder2_at', 'login_token_expires', 'download_token_expires'], true)) {
                $formatted = (int)$value > 0
                    ? s(userdate((int)$value, get_string('strftimedatetimeshort', 'langconfig')))
                    : '—';
            } else if ($field === 'payment_link' || $field === 'http_referer') {
                $formatted = trim((string)$value) === ''
                    ? '—'
                    : html_writer::link((string)$value, s((string)$value), ['target' => '_blank', 'rel' => 'noopener noreferrer']);
            } else if ($field === 'response_json') {
                $decoded = json_decode((string)$value, true);
                $json = json_encode($decoded ?? $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $formatted = html_writer::tag('pre', s((string)$json), ['class' => 'small bg-light p-3 rounded overflow-auto']);
            } else if ($field === 'last_error' || $field === 'created_useragent') {
                $formatted = html_writer::tag('pre', s((string)$value), ['class' => 'small mb-0 text-break']);
            } else {
                $formatted = s((string)$value);
            }
            $detailrows[] = [get_string('commerce_purchase_payment_request_field_' . $field, 'local_subscriptions'), $formatted];
        }
        $requestblocks .= html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                get_string('commerce_purchase_payment_request_summary', 'local_subscriptions', (object)[
                    'family' => $request->family,
                    'id' => $request->id,
                ]),
                ['class' => 'fw-semibold']
            ) . html_writer::div($definition($detailrows), 'mt-3'),
            [
                'id' => 'commerce-payment-request-' . $request->family . '-' . $request->id,
                'class' => 'card card-body mb-3',
            ]
        );
    }
    echo CommerceDesignSystemRenderer::panel(
        get_string('commerce_purchase_payment_requests_section', 'local_subscriptions'),
        $requestblocks,
        'mt-4'
    );
}

$grantrender = '';
if ($purchase->grants === []) {
    $grantrender = html_writer::tag('p', get_string('commerce_purchase_no_grants', 'local_subscriptions'), [
        'class' => 'text-muted mb-0',
    ]);
} else {
    $granttable = new html_table();
    $granttable->head = [
        get_string('commerce_purchase_grant_type', 'local_subscriptions'),
        get_string('commerce_purchase_resource', 'local_subscriptions'),
        get_string('commerce_purchase_status', 'local_subscriptions'),
        get_string('commerce_purchase_beneficiary', 'local_subscriptions'),
        get_string('commerce_purchase_reference', 'local_subscriptions'),
    ];
    $granttable->attributes['class'] = 'generaltable table align-middle';
    foreach ($purchase->grants as $grant) {
        $beneficiary = $grant->beneficiaryuserid === null
            ? s($grant->beneficiaryemail)
            : s($grant->beneficiaryemail) . html_writer::div('#' . $grant->beneficiaryuserid, 'small text-muted');
        $granttable->data[] = [
            s(CommercePurchasePresentation::fulfillment_label($grant->type)),
            html_writer::tag('code', s($grant->resourcekey), ['class' => 'small']),
            CommercePurchasePresentation::technical_status_badge('fulfillment', $grant->status),
            $beneficiary,
            html_writer::tag('code', s($grant->reference), ['class' => 'small']),
        ];
    }
    $grantrender = html_writer::table($granttable);
}
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_grants_section', 'local_subscriptions'),
    $grantrender,
    'mt-4'
);

$fulfillmentrender = '';
if ($purchase->fulfillments === []) {
    $fulfillmentrender = html_writer::tag('p', get_string('commerce_purchase_no_fulfillments', 'local_subscriptions'), [
        'class' => 'text-muted mb-0',
    ]);
} else {
    foreach ($purchase->fulfillments as $fulfillment) {
        $handler = $fulfillment->handlerclass === null
            ? '—'
            : html_writer::tag('code', s($fulfillment->handlerclass), ['class' => 'small']);
        $duration = $fulfillment->duration();
        $rows = [
            [get_string('commerce_purchase_fulfillment', 'local_subscriptions'), s(CommercePurchasePresentation::fulfillment_label($fulfillment->key))],
            [get_string('commerce_purchase_status', 'local_subscriptions'), CommercePurchasePresentation::technical_status_badge('fulfillment', $fulfillment->status)],
            [get_string('commerce_purchase_handler', 'local_subscriptions'), $handler],
            [get_string('commerce_purchase_attempts', 'local_subscriptions'), (string)$fulfillment->attempts],
            [get_string('commerce_purchase_duration', 'local_subscriptions'), $duration === null ? '—' : get_string('commerce_purchase_duration_seconds', 'local_subscriptions', $duration)],
            [get_string('commerce_purchase_source', 'local_subscriptions'), s($fulfillment->source ?? '—')],
            [get_string('commerce_purchase_execution_reference', 'local_subscriptions'), html_writer::tag('code', s($fulfillment->executionreference ?? '—'), ['class' => 'small'])],
        ];
        if ($fulfillment->message !== null && trim($fulfillment->message) !== '') {
            $rows[] = [get_string('commerce_purchase_message', 'local_subscriptions'), s($fulfillment->message)];
        }
        if ($fulfillment->errorclass !== null && trim($fulfillment->errorclass) !== '') {
            $rows[] = [get_string('commerce_purchase_error', 'local_subscriptions'), html_writer::tag('code', s($fulfillment->errorclass), ['class' => 'text-danger small'])];
        }
        $fulfillmentrender .= html_writer::div($definition($rows), 'border rounded p-3 mb-3');
    }
}
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_fulfillments_section', 'local_subscriptions'),
    $fulfillmentrender,
    'mt-4'
);

$attemptrender = '';
if ($purchase->fulfillmentattempts === []) {
    $attemptrender = html_writer::tag('p', get_string('commerce_purchase_no_fulfillment_attempts', 'local_subscriptions'), [
        'class' => 'text-muted mb-0',
    ]);
} else {
    $attempttable = new html_table();
    $attempttable->head = [
        get_string('date'),
        get_string('commerce_purchase_status', 'local_subscriptions'),
        get_string('commerce_purchase_handler', 'local_subscriptions'),
        get_string('commerce_purchase_duration', 'local_subscriptions'),
        get_string('commerce_purchase_source', 'local_subscriptions'),
        get_string('commerce_purchase_message', 'local_subscriptions'),
    ];
    $attempttable->attributes['class'] = 'generaltable table align-middle';
    foreach ($purchase->fulfillmentattempts as $attempt) {
        $duration = $attempt->duration();
        $message = $attempt->message ?? '';
        if ($attempt->errorclass !== null) {
            $message .= ($message === '' ? '' : '<br>') . html_writer::tag('code', s($attempt->errorclass), ['class' => 'text-danger small']);
        }
        $attempttable->data[] = [
            s(userdate($attempt->timestarted, get_string('strftimedatetimeshort', 'langconfig'))),
            CommercePurchasePresentation::technical_status_badge('fulfillment', $attempt->status),
            html_writer::tag('code', s($attempt->handlerclass), ['class' => 'small']),
            $duration === null ? '—' : get_string('commerce_purchase_duration_seconds', 'local_subscriptions', $duration),
            s($attempt->source),
            $message === '' ? '—' : $message,
        ];
    }
    $attemptrender = html_writer::table($attempttable);
}
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_fulfillment_attempts_section', 'local_subscriptions'),
    $attemptrender,
    'mt-4'
);

$actionpolicy = new CommercePurchaseActionPolicy();
$actionservice = CommercePurchaseActionServiceFactory::create();
$isclosedwithoutdelivery = $actionservice->is_closed_without_fulfillment($id);
$actionhtml = '';
if (!$isclosedwithoutdelivery
        && $actionpolicy->can_retry_fulfillment($purchase)
        && has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
    $retryurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/retry_fulfillment.php', [
        'id' => $id,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);
    $fulfillmentactionkey = $purchase->fulfillments === []
        ? 'commerce_purchase_start_fulfillment'
        : 'commerce_purchase_retry_fulfillment';
    $fulfillmentconfirmkey = $purchase->fulfillments === []
        ? 'commerce_purchase_start_fulfillment_confirm'
        : 'commerce_purchase_retry_confirm';
    $actionhtml .= html_writer::link($retryurl, get_string($fulfillmentactionkey, 'local_subscriptions'), [
        'class' => 'btn btn-warning me-2',
        'data-confirmation' => 'modal',
        'data-confirmation-title-str' => json_encode([$fulfillmentactionkey, 'local_subscriptions']),
        'data-confirmation-content-str' => json_encode([$fulfillmentconfirmkey, 'local_subscriptions']),
        'data-confirmation-yes-button-str' => json_encode(['yes']),
        'data-confirmation-destination' => $retryurl->out(false),
    ]);
}
if ($isclosedwithoutdelivery) {
    $actionhtml .= html_writer::div(
        get_string('commerce_purchase_closed_without_fulfillment_notice', 'local_subscriptions'),
        'alert alert-secondary mb-3'
    );
}

$noteform = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => (new moodle_url('/local/subscriptions/admin/commerce/purchases/add_note.php'))->out(false),
    'class' => 'mt-3',
]);
$noteform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
$noteform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
$noteform .= html_writer::tag('label', s(get_string('commerce_purchase_internal_note', 'local_subscriptions')), [
    'for' => 'commerce-purchase-note',
    'class' => 'form-label',
]);
$noteform .= html_writer::tag('textarea', '', [
    'id' => 'commerce-purchase-note',
    'name' => 'note',
    'rows' => 3,
    'maxlength' => 2000,
    'required' => 'required',
    'class' => 'form-control',
]);
$noteform .= html_writer::tag('button', s(get_string('commerce_purchase_add_note', 'local_subscriptions')), [
    'type' => 'submit',
    'class' => 'btn btn-outline-primary mt-2',
]);
$noteform .= html_writer::end_tag('form');
$actionhtml .= $noteform;
$actionhtml .= html_writer::tag('p', s(get_string('commerce_purchase_destructive_actions_deferred', 'local_subscriptions')), [
    'class' => 'small text-muted mt-3 mb-0',
]);
echo CommerceDesignSystemRenderer::panel(get_string('commerce_purchase_actions_section', 'local_subscriptions'), $actionhtml, 'mt-4');

$diagnostic = $definition([
    ['UUID', html_writer::tag('code', s($summary->uuid))],
    [get_string('commerce_purchase_source', 'local_subscriptions'), s($summary->source)],
    [get_string('commerce_purchase_legacy_family', 'local_subscriptions'), s($purchase->legacyfamily ?? '—')],
    [get_string('commerce_purchase_legacy_id', 'local_subscriptions'), s((string)($purchase->legacyid ?? '—'))],
]);
echo html_writer::tag('details', html_writer::tag('summary', s(get_string('commerce_purchase_diagnostics_section', 'local_subscriptions')), ['class' => 'fw-semibold']) . html_writer::div($diagnostic, 'mt-3'), ['class' => 'card card-body mt-4']);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
