<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\action\CommercePurchaseAdminClosureService;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\communication\CommercePurchaseCurrentCustomerResolver;
use local_subscriptions\commerce\mail\sales\CommerceSalesFollowupService;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\order\document\CommerceOrderDocumentHistoryRepository;
use local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\refund\CommerceRefundReasonPresenter;
use local_subscriptions\commerce\payment\method\CommercePersistedPaymentMethodResolver;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodVisual;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundService;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
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
$actionpolicy = new CommercePurchaseActionPolicy();
$adminclosureservice = new CommercePurchaseAdminClosureService($DB);
$salesfollowupservice = CommerceSalesFollowupService::create($DB);
$refundrepository = new CommercePaymentRefundRepository($DB);
$paymentproviderregistry =
    CommerceRuntimeFactory::create()->payment_providers();
$refundservice =
    new CommercePaymentRefundService($paymentproviderregistry);
$rightsrevocationservice = CommercePurchaseRightsRevocationService::create($DB);
$revocablerights = $rightsrevocationservice->preview($summary->reference);
$paymentmethodresolver = new CommercePersistedPaymentMethodResolver();
$documenthistoryrepository = new CommerceOrderDocumentHistoryRepository($DB);
$refundhistoryproviders = [];
foreach ($paymentproviderregistry->all() as $paymentprovider) {
    if (
        $paymentprovider
            instanceof CommerceRefundHistoryCapablePaymentProvider
        && $paymentprovider->is_available()
    ) {
        $refundhistoryproviders[$paymentprovider->get_key()] = true;
    }
}
$currentcustomer = CommercePurchaseCurrentCustomerResolver::create()->resolve($purchase);
$currentemail = trim((string)$currentcustomer->email);
$historicalemail = trim((string)$summary->customer->email);
$stripereconciled = optional_param('stripe_reconciled', 0, PARAM_BOOL);
$paypalreconciled = optional_param('paypal_reconciled', 0, PARAM_BOOL);
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
if ($summary->adminclosed) {
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-archive me-2',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_sales_closed_banner',
            'local_subscriptions',
            userdate(
                $summary->adminclosedat,
                get_string('strftimedatetimeshort', 'langconfig')
            )
        ),
        'alert alert-secondary py-2'
    );
}
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PURCHASES);
$refundcreated = optional_param('refund_created', 0, PARAM_INT);
$rightsrevoked = optional_param('rights_revoked', 0, PARAM_BOOL);
$rightsrevokefailed = optional_param('rights_revoke_failed', 0, PARAM_BOOL);
if ($refundcreated > 0) {
    echo html_writer::div(
        s(get_string(
            'commerce_refund_created_notice',
            'local_subscriptions'
        )),
        'alert alert-success mt-3'
    );
}
if ($rightsrevoked) {
    echo html_writer::div(
        s(get_string(
            'commerce_rights_revoked_notice',
            'local_subscriptions'
        )),
        'alert alert-warning mt-3'
    );
}
if ($rightsrevokefailed) {
    echo html_writer::div(
        s(get_string(
            'commerce_rights_revoke_failed_notice',
            'local_subscriptions'
        )),
        'alert alert-warning mt-3'
    );
}
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
if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)
        && $salesfollowupservice->is_summary_eligible($summary)) {
    $quickactions .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/followup_mail.php',
            ['id' => $id]
        ),
        get_string('commerce_sales_followup_action', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    );
}

if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)
        && $actionpolicy->can_resend_receipt_summary($summary)) {
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

if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)
        && $actionpolicy->can_resend_access_summary($summary)) {
    $resendaccessurl = new moodle_url(
        '/local/subscriptions/admin/commerce/purchases/resend_access.php',
        [
            'id' => $id,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]
    );
    $quickactions .= html_writer::link(
        $resendaccessurl,
        get_string('commerce_purchase_resend_access', 'local_subscriptions'),
        [
            'class' => 'btn btn-outline-success',
            'data-confirmation' => 'modal',
            'data-confirmation-title-str' => json_encode([
                'commerce_purchase_resend_access',
                'local_subscriptions',
            ]),
            'data-confirmation-content-str' => json_encode([
                'commerce_purchase_resend_access_confirm',
                'local_subscriptions',
                $currentemail,
            ]),
            'data-confirmation-yes-button-str' => json_encode(['yes']),
            'data-confirmation-destination' => $resendaccessurl->out(false),
        ]
    );
}
if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)
        && $revocablerights !== []) {
    $quickactions .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/revoke_rights.php',
            ['id' => $id]
        ),
        get_string('commerce_rights_revoke_action', 'local_subscriptions'),
        ['class' => 'btn btn-outline-danger']
    );
}

if ($currentcustomer->userid !== null || $currentemail !== '') {
    $user360params = $currentcustomer->userid !== null
        ? ['id' => $currentcustomer->userid]
        : ['email' => $currentemail];
    $quickactions .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/users/view.php', $user360params),
        get_string('commerce_purchase_open_user360', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
}
if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
        && $actionpolicy->can_create_personal_offer_summary($summary)) {
    $quickactions .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/create.php',
            [
                'prefillemail' => $currentemail !== ''
                    ? $currentemail
                    : $summary->customer->email,
                'prefillsourcemode' => 'purchase',
                'prefillsourcepurchase' => $summary->reference,
            ]
        ),
        get_string('commerce_sales_action_create_offer', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
}
if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
    $returnurl = (new moodle_url(
        '/local/subscriptions/admin/commerce/purchases/view.php',
        ['id' => $id]
    ))->out_as_local_url(false);

    if ($summary->adminclosed) {
        $quickactions .= html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/admin_state.php',
                [
                    'id' => $id,
                    'action' => 'reopen',
                    'sesskey' => sesskey(),
                    'returnurl' => $returnurl,
                ]
            ),
            get_string('commerce_sales_action_reopen', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary']
        );
    } elseif ($adminclosureservice->can_close($summary)) {
        $closeurl = new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/admin_state.php',
            [
                'id' => $id,
                'action' => 'close',
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]
        );
        $quickactions .= html_writer::link(
            $closeurl,
            get_string('commerce_sales_action_close', 'local_subscriptions'),
            [
                'class' => 'btn btn-outline-danger',
                'data-confirmation' => 'modal',
                'data-confirmation-title-str' => json_encode([
                    'commerce_sales_action_close',
                    'local_subscriptions',
                ]),
                'data-confirmation-content-str' => json_encode([
                    'commerce_sales_action_close_confirm',
                    'local_subscriptions',
                ]),
                'data-confirmation-yes-button-str' => json_encode([
                    'commerce_sales_action_close',
                    'local_subscriptions',
                ]),
                'data-confirmation-destination' => $closeurl->out(false),
            ]
        );
    }
}
$quickactions .= html_writer::end_div();
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_actions_section', 'local_subscriptions'),
    $quickactions,
    'mt-3'
);

$issuedinvoice = $documenthistoryrepository->invoice_for_purchase($summary->id);
$creditnotes = $documenthistoryrepository->credit_notes_for_purchase($summary->id);
$documenttable = new html_table();
$documenttable->attributes['class'] = 'generaltable table align-middle mb-0';
$documenttable->head = [
    get_string('commerce_document_type', 'local_subscriptions'),
    get_string('commerce_document_number', 'local_subscriptions'),
    get_string('date'),
    get_string('commerce_purchase_amount', 'local_subscriptions'),
    get_string('actions'),
];
$documenttable->data[] = [
    s(get_string('commerce_document_invoice', 'local_subscriptions')),
    $issuedinvoice === null
        ? html_writer::span(
            s(get_string('commerce_document_invoice_not_issued', 'local_subscriptions')),
            'text-muted'
        )
        : html_writer::tag('code', s((string)$issuedinvoice['number'])),
    $issuedinvoice === null
        ? '—'
        : s(userdate(
            (int)$issuedinvoice['issuedat'],
            get_string('strftimedatetimeshort', 'langconfig')
        )),
    CommercePurchasePresentation::money($summary->totalminor, $summary->currency),
    html_writer::link(
        new moodle_url('/local/subscriptions/order_invoice.php', [
            'reference' => $summary->reference,
        ]),
        get_string('commerce_i410_download_invoice', 'local_subscriptions'),
        [
            'class' => 'btn btn-sm btn-outline-primary',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]
    ),
];

foreach ($creditnotes as $creditnote) {
    $documenttable->data[] = [
        s(get_string('commerce_document_credit_note', 'local_subscriptions')),
        html_writer::tag('code', s($creditnote->number)),
        s(userdate(
            $creditnote->issuedat,
            get_string('strftimedatetimeshort', 'langconfig')
        )),
        CommercePurchasePresentation::money(
            (int)($creditnote->financial['refund_minor'] ?? 0),
            (string)($creditnote->financial['currency'] ?? $summary->currency)
        ),
        html_writer::link(
            new moodle_url('/local/subscriptions/order_credit_note.php', [
                'reference' => $summary->reference,
                'creditnoteid' => $creditnote->id,
            ]),
            get_string('commerce_document_download_credit_note', 'local_subscriptions'),
            [
                'class' => 'btn btn-sm btn-outline-primary',
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]
        ),
    ];
}

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_documents_title', 'local_subscriptions'),
    html_writer::table($documenttable),
    'mt-3'
);

$legalsnapshotmetadata = is_array($purchase->snapshot['metadata'] ?? null)
    ? $purchase->snapshot['metadata']
    : [];
$legalsnapshot = is_array($legalsnapshotmetadata['legal_entity_snapshot'] ?? null)
    ? $legalsnapshotmetadata['legal_entity_snapshot']
    : [];

$sellerrows = [];
$sellersnapshothelp = '';

if ($legalsnapshot !== []) {
    $sellerrows = [
        [
            get_string('commerce_purchase_legal_entity', 'local_subscriptions'),
            html_writer::tag(
                'strong',
                s((string)($legalsnapshot['name'] ?? '—'))
            )
            . ' '
            . html_writer::tag(
                'code',
                s((string)($legalsnapshot['legal_entity_key'] ?? '—'))
            ),
        ],
        [
            get_string('commerce_purchase_market_country', 'local_subscriptions'),
            html_writer::tag(
                'code',
                s((string)($legalsnapshot['market_country'] ?? 'ZZ'))
            ),
        ],
        [
            get_string('commerce_purchase_registered_country', 'local_subscriptions'),
            html_writer::tag(
                'code',
                s((string)($legalsnapshot['registered_country'] ?? '—'))
            ),
        ],
        [
            get_string('commerce_purchase_merchant_resolution_rule', 'local_subscriptions'),
            html_writer::tag(
                'code',
                s((string)($legalsnapshot['resolution_rule'] ?? '—'))
            ),
        ],
        [
            get_string('commerce_purchase_seller_snapshot_date', 'local_subscriptions'),
            !empty($legalsnapshot['resolved_at'])
                ? s(userdate(
                    (int)$legalsnapshot['resolved_at'],
                    get_string('strftimedatetimeshort', 'langconfig')
                ))
                : '—',
        ],
    ];
    $sellersnapshothelp = get_string(
        'commerce_purchase_seller_snapshot_help',
        'local_subscriptions'
    );
} elseif ($issuedinvoice !== null && is_array($issuedinvoice['seller'] ?? null)) {
    $invoiceseller = $issuedinvoice['seller'];
    $sellerrows = [
        [
            get_string('commerce_purchase_legal_entity', 'local_subscriptions'),
            html_writer::tag(
                'strong',
                s((string)($invoiceseller['name'] ?? '—'))
            )
            . ' '
            . html_writer::tag(
                'code',
                s((string)($issuedinvoice['entitykey'] ?? '—'))
            ),
        ],
        [
            get_string('commerce_purchase_registered_country', 'local_subscriptions'),
            html_writer::tag(
                'code',
                s((string)($invoiceseller['registered_country'] ?? '—'))
            ),
        ],
        [
            get_string('commerce_purchase_seller_snapshot_source', 'local_subscriptions'),
            s(get_string(
                'commerce_purchase_seller_snapshot_source_invoice',
                'local_subscriptions'
            )),
        ],
    ];
    $sellersnapshothelp = get_string(
        'commerce_purchase_seller_snapshot_invoice_help',
        'local_subscriptions'
    );
} else {
    $sellerrows = [[
        get_string('commerce_purchase_seller_snapshot_status', 'local_subscriptions'),
        html_writer::span(
            s(get_string(
                'commerce_purchase_seller_snapshot_unavailable',
                'local_subscriptions'
            )),
            'text-muted'
        ),
    ]];
    $sellersnapshothelp = get_string(
        'commerce_purchase_seller_snapshot_unavailable_help',
        'local_subscriptions'
    );
}

echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_seller_snapshot_title', 'local_subscriptions'),
    $definition($sellerrows)
        . html_writer::div(
            $sellersnapshothelp,
            'small text-muted mt-2'
        ),
    'mt-3 mb-3'
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
if ($stripereconciled) {
    echo html_writer::div(
        s(
            get_string(
                'commerce_stripe_crm_success',
                'local_subscriptions'
            )
        ),
        'alert alert-success mt-3'
    );
}

if ($paypalreconciled) {
    echo html_writer::div(
        s(
            get_string(
                'commerce_paypal_crm_success',
                'local_subscriptions'
            )
        ),
        'alert alert-success mt-3'
    );
}

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

if ($summary->provider === Provider::STRIPE) {
    $pendingstripe = !in_array(
        $summary->paymentstatus,
        ['paid', 'completed', 'succeeded'],
        true
    )
        || $summary->commercialstatus !== 'fulfilled';

    $stripecontent = html_writer::div(
        s(
            get_string(
                $pendingstripe
                    ? 'commerce_stripe_crm_purchase_pending_help'
                    : 'commerce_stripe_crm_purchase_complete_help',
                'local_subscriptions'
            )
        ),
        'text-muted mb-3'
    );

    $stripecontent .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/reconcile_stripe.php',
            ['id' => $id]
        ),
        get_string(
            'commerce_stripe_crm_verify',
            'local_subscriptions'
        ),
        [
            'class' => $pendingstripe
                ? 'btn btn-primary'
                : 'btn btn-outline-primary',
        ]
    );

    echo CommerceDesignSystemRenderer::panel(
        get_string(
            'commerce_stripe_crm_purchase_panel',
            'local_subscriptions'
        ),
        $stripecontent,
        'mt-4'
    );
}


if ($summary->provider === Provider::PAYPAL) {
    $pendingpaypal = !in_array(
        $summary->paymentstatus,
        ['paid', 'completed', 'succeeded'],
        true
    )
        || $summary->commercialstatus !== 'fulfilled';

    $paypalcontent = html_writer::div(
        s(
            get_string(
                $pendingpaypal
                    ? 'commerce_paypal_crm_purchase_pending_help'
                    : 'commerce_paypal_crm_purchase_complete_help',
                'local_subscriptions'
            )
        ),
        'text-muted mb-3'
    );

    $paypalcontent .= html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/reconcile_paypal.php',
            ['id' => $id]
        ),
        get_string(
            'commerce_paypal_crm_verify',
            'local_subscriptions'
        ),
        [
            'class' => $pendingpaypal
                ? 'btn btn-primary'
                : 'btn btn-outline-primary',
        ]
    );

    echo CommerceDesignSystemRenderer::panel(
        get_string(
            'commerce_paypal_crm_purchase_panel',
            'local_subscriptions'
        ),
        $paypalcontent,
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
    [
        get_string('commerce_i410_payment_method', 'local_subscriptions'),
        CommercePaymentMethodVisual::label_with_icon(
            $summary->paymentmethod,
            $paymentmethodresolver->label($summary->paymentmethod),
            20
        ),
    ],
    [get_string('commerce_purchase_provider', 'local_subscriptions'), $summary->provider === null ? '—' : Provider::label_with_icon($summary->provider)],
]));
echo html_writer::end_div();
echo html_writer::start_div('col-lg-6');
$customername = $currentcustomer->display_name();
$customeractions = '';
if ($currentcustomer->userid !== null || $currentemail !== '') {
    $customeractions = html_writer::start_div('d-flex flex-wrap gap-2 mt-2');
    $user360params = $currentcustomer->userid !== null
        ? ['id' => $currentcustomer->userid]
        : ['email' => $currentemail];
    $customeractions .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/users/view.php', $user360params),
        get_string('commerce_purchase_open_user360', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );
    if ($currentcustomer->userid !== null) {
        $customeractions .= html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $currentcustomer->userid]),
            get_string('commerce_purchase_open_moodle_profile', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );
    }
    $customeractions .= html_writer::end_div();
}
$customerrows = [
    [get_string('name'), s($customername !== '' ? $customername : '—')],
    [get_string('email'), s($currentemail !== '' ? $currentemail : '—')],
];
if ($historicalemail !== '' && core_text::strtolower($historicalemail) !== core_text::strtolower($currentemail)) {
    $customerrows[] = [
        get_string('commerce_purchase_historical_email', 'local_subscriptions'),
        html_writer::tag('span', s($historicalemail), ['class' => 'text-muted'])
    ];
}
$customerrows[] = [
    get_string('commerce_purchase_identifier', 'local_subscriptions'),
    s((string)($currentcustomer->userid ?? '—'))
];
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_purchase_customer_section', 'local_subscriptions'),
    $definition($customerrows) . $customeractions
);
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
    get_string('commerce_i410_payment_method', 'local_subscriptions'),
    get_string('commerce_purchase_provider', 'local_subscriptions'),
    get_string('commerce_purchase_amount', 'local_subscriptions'),
    get_string('commerce_refund_refunded_column', 'local_subscriptions'),
    get_string('commerce_purchase_provider_reference', 'local_subscriptions'),
    get_string('date'),
    get_string('commerce_purchase_payment_request', 'local_subscriptions'),
    get_string('commerce_purchase_actions_section', 'local_subscriptions'),
];
$paymenttable->attributes['class'] = 'generaltable table align-middle';

$refundhistory = '';

foreach ($purchase->payments as $payment) {
    $providerhtml = $payment->provider === null
        ? '—'
        : Provider::label_with_icon($payment->provider);

    $requesthtml = get_string(
        'commerce_purchase_native_payment_attempt',
        'local_subscriptions'
    );

    if ($payment->paymentrequest !== null) {
        $request = $payment->paymentrequest;
        $requesthtml = html_writer::link(
            '#commerce-payment-request-' . $request->family . '-' . $request->id,
            get_string(
                'commerce_purchase_payment_request_open',
                'local_subscriptions',
                $request->id
            ),
            ['class' => 'small fw-semibold']
        );
    }

    $paymentrefunds = $payment->id !== null
        ? $refundrepository->find_for_payment($payment->id)
        : [];

    $remainingminor = $payment->id !== null
        ? $refundrepository->refundable_amount_minor(
            $payment->id,
            $payment->amountminor
        )
        : 0;

    $refundedminor = max(
        0,
        $payment->amountminor - $remainingminor
    );

    $paymentactions = '—';
    $paymentispaid = in_array(
        $payment->status,
        ['paid', 'completed', 'succeeded'],
        true
    );

    if (
        $payment->id !== null
        && $payment->provider !== null
        && $paymentispaid
        && $remainingminor > 0
        && has_capability(
            Capabilities::MANAGE_SUBSCRIPTIONS,
            $context
        )
        && $refundservice->is_supported($payment->provider)
    ) {
        $paymentactions = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/refund.php',
                [
                    'id' => $id,
                    'paymentid' => $payment->id,
                ]
            ),
            get_string(
                'commerce_refund_action',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-sm btn-outline-danger']
        );
    }

    if (
        $payment->id !== null
        && $payment->provider !== null
        && isset(
            $refundhistoryproviders[
                strtolower((string)$payment->provider)
            ]
        )
        && has_capability(
            Capabilities::MANAGE_SUBSCRIPTIONS,
            $context
        )
    ) {
        $syncrefundurl = new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/sync_refunds.php',
            [
                'id' => $id,
                'paymentid' => $payment->id,
                'sesskey' => sesskey(),
            ]
        );

        $synclink = html_writer::link(
            $syncrefundurl,
            get_string(
                'commerce_refund_sync_action',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-sm btn-outline-secondary ms-1']
        );

        $paymentactions = $paymentactions === '—'
            ? $synclink
            : $paymentactions . $synclink;
    }

    $paymenttable->data[] = [
        CommercePurchasePresentation::technical_status_badge(
            'payment',
            $payment->status
        ),
        CommercePaymentMethodVisual::label_with_icon(
            $payment->paymentmethod,
            $paymentmethodresolver->label($payment->paymentmethod),
            20
        ),
        $providerhtml,
        CommercePurchasePresentation::money(
            $payment->amountminor,
            $payment->currency
        ),
        $refundedminor > 0
            ? CommercePurchasePresentation::money(
                $refundedminor,
                $payment->currency
            )
            : '—',
        html_writer::tag(
            'code',
            s(
                $payment->transactionid
                ?? $payment->providerreference
                ?? '—'
            ),
            ['class' => 'small']
        ),
        $payment->paidat === null
            ? '—'
            : s(userdate(
                $payment->paidat,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            )),
        $requesthtml,
        $paymentactions,
    ];

    if ($paymentrefunds !== []) {
        $refundtable = new html_table();
        $refundtable->attributes['class'] =
            'table table-sm align-middle mb-0';
        $refundtable->head = [
            get_string('date'),
            get_string(
                'commerce_purchase_status',
                'local_subscriptions'
            ),
            get_string(
                'commerce_refund_amount',
                'local_subscriptions'
            ),
            get_string(
                'commerce_refund_reason',
                'local_subscriptions'
            ),
            get_string(
                'commerce_refund_provider_reference',
                'local_subscriptions'
            ),
        ];

        foreach ($paymentrefunds as $refund) {
            $refundtable->data[] = [
                s(userdate(
                    $refund->get_time_created(),
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                )),
                html_writer::span(
                    get_string(
                        'commerce_refund_status_'
                        . $refund->get_status(),
                        'local_subscriptions'
                    ),
                    'badge rounded-pill '
                        . (
                            $refund->get_status() === 'succeeded'
                                ? 'text-bg-success'
                                : (
                                    $refund->get_status() === 'failed'
                                        ? 'text-bg-danger'
                                        : 'text-bg-warning'
                                )
                        )
                ),
                CommercePurchasePresentation::money(
                    $refund->get_amount_minor(),
                    $refund->get_currency()
                ),
                s(
                    (new CommerceRefundReasonPresenter())->label(
                        $refund->get_reason()
                    ) ?: get_string(
                        'commerce_refund_reason_unspecified',
                        'local_subscriptions'
                    )
                ),
                html_writer::tag(
                    'code',
                    s(
                        $refund->get_provider_refund_id()
                        ?? '—'
                    ),
                    ['class' => 'small']
                ),
            ];
        }

        $refundhistory .= html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                get_string(
                    'commerce_refund_history_payment',
                    'local_subscriptions',
                    (object)[
                        'provider' => Provider::get(
                            (string)$payment->provider
                        ),
                        'amount' =>
                            CommercePurchasePresentation::money(
                                $refundedminor,
                                $payment->currency
                            ),
                    ]
                ),
                ['class' => 'fw-semibold']
            )
            . html_writer::div(
                html_writer::table($refundtable),
                'mt-3'
            ),
            ['class' => 'card card-body mb-3']
        );
    }
}

$paymentcontent = $purchase->payments === []
    ? html_writer::tag(
        'p',
        get_string(
            'commerce_purchase_no_payments',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    )
    : html_writer::table($paymenttable);

if ($refundhistory !== '') {
    $paymentcontent .= html_writer::tag(
        'h3',
        get_string(
            'commerce_refund_history_title',
            'local_subscriptions'
        ),
        ['class' => 'h6 mt-4 mb-3']
    );
    $paymentcontent .= $refundhistory;
}

echo CommerceDesignSystemRenderer::panel(
    get_string(
        'commerce_purchase_payments_section',
        'local_subscriptions'
    ),
    $paymentcontent,
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
