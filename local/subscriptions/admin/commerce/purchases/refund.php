<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\currency\CommerceCurrencyAmount;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundCommand;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundException;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundService;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\payment\Provider;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$purchaseid = required_param('id', PARAM_INT);
$paymentid = required_param('paymentid', PARAM_INT);

$purchaserepository = new CommercePurchaseReadRepository($DB);
$purchase = $purchaserepository->find_by_id($purchaseid);

if ($purchase === null) {
    throw new moodle_exception(
        'commerce_purchase_not_found',
        'local_subscriptions'
    );
}

$paymentrepository = new CommercePaymentRepository($DB);
$payment = $paymentrepository->find($paymentid);

if (
    $payment === null
    || $payment->get_purchase_uuid() !== $purchase->summary->uuid
) {
    throw new moodle_exception(
        'commerce_refund_payment_not_found',
        'local_subscriptions'
    );
}

$refundrepository = new CommercePaymentRefundRepository($DB);
$providerregistry = CommerceRuntimeFactory::create()->payment_providers();
$refundservice = new CommercePaymentRefundService($providerregistry);
$revocationservice = CommercePurchaseRightsRevocationService::create($DB);
$revocable = $revocationservice->preview($purchase->summary->reference);

if (!$refundservice->is_supported($payment->get_provider())) {
    throw new moodle_exception(
        'commerce_refund_provider_not_supported',
        'local_subscriptions',
        '',
        Provider::get($payment->get_provider())
    );
}

$remainingminor = $refundrepository->refundable_amount_minor(
    $paymentid,
    $payment->get_amount_minor()
);

if ($remainingminor <= 0) {
    throw new moodle_exception(
        'commerce_refund_nothing_remaining',
        'local_subscriptions'
    );
}

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/refund.php',
    [
        'id' => $purchaseid,
        'paymentid' => $paymentid,
    ]
);
$returnurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/view.php',
    ['id' => $purchaseid]
);

$title = get_string(
    'commerce_refund_page_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-refund-page'
);

$token = optional_param(
    'refundtoken',
    bin2hex(random_bytes(12)),
    PARAM_ALPHANUM
);

if (data_submitted()) {
    require_sesskey();

    $rawamount = trim(
        required_param('amount', PARAM_RAW_TRIMMED)
    );
    $reason = optional_param(
        'reason',
        'requested_by_customer',
        PARAM_ALPHANUMEXT
    );
    $confirmed = optional_param(
        'confirmrefund',
        0,
        PARAM_BOOL
    );
    $revokeaccess = optional_param(
        'revokeaccess',
        0,
        PARAM_BOOL
    );

    if (!$confirmed) {
        redirect(
            $pageurl,
            get_string(
                'commerce_refund_confirmation_required',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    try {
        $amount = CommerceCurrencyAmount::from_major_input(
            $rawamount,
            $payment->get_currency()
        );
        $amountminor = $amount->get_amount_minor();

        if ($amountminor <= 0 || $amountminor > $remainingminor) {
            throw new \coding_exception(
                'Refund amount exceeds remaining refundable amount.'
            );
        }
    } catch (\Throwable $exception) {
        redirect(
            $pageurl,
            get_string(
                'commerce_refund_invalid_amount',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    try {
        $command = new CommercePaymentRefundCommand(
            $paymentrepository,
            $refundrepository,
            $refundservice
        );

        $refund = $command->execute(
            $paymentid,
            $amountminor,
            'admin-refund-' . $paymentid . '-' . $token,
            $reason,
            (int)$USER->id,
            [
                'source' => 'crm_purchase',
                'purchaseid' => $purchaseid,
                'purchasereference' =>
                    $purchase->summary->reference,
                'revoke_rights_requested' => $revokeaccess ? 1 : 0,
            ]
        );

        $revocationresult = null;
        if ($revokeaccess) {
            try {
                $revocationresult = $revocationservice->revoke(
                    $purchase->summary->reference,
                    (int)$USER->id,
                    'refund:' . $reason,
                    null,
                    \local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository::REFUNDED
                );
            } catch (\Throwable $revocationexception) {
                error_log(
                    '[commerce][rights_revocation] purchase='
                    . $purchase->summary->reference
                    . ' exception=' . $revocationexception::class
                    . ' message=' . $revocationexception->getMessage()
                );

                redirect(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/view.php',
                        [
                            'id' => $purchaseid,
                            'refund_created' => $refund->get_id(),
                            'rights_revoke_failed' => 1,
                        ]
                    ),
                    get_string(
                        'commerce_refund_success_rights_failed',
                        'local_subscriptions',
                        CommercePurchasePresentation::money(
                            $refund->get_amount_minor(),
                            $refund->get_currency()
                        )
                    ),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
        }

        redirect(
            new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/view.php',
                [
                    'id' => $purchaseid,
                    'refund_created' => $refund->get_id(),
                    'rights_revoked' => $revocationresult !== null && $revocationresult['revoked'] > 0 ? 1 : 0,
                ]
            ),
            get_string(
                'commerce_refund_success',
                'local_subscriptions',
                CommercePurchasePresentation::money(
                    $refund->get_amount_minor(),
                    $refund->get_currency()
                )
            ),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (CommercePaymentRefundException $exception) {
        error_log(
            '[commerce][refund] payment=' . $paymentid
            . ' provider=' . $payment->get_provider()
            . ' code=' . $exception->get_code_key()
            . ' message=' . $exception->getMessage()
        );

        redirect(
            $pageurl,
            get_string(
                'commerce_refund_failed',
                'local_subscriptions',
                $exception->getMessage()
            ),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    } catch (\Throwable $exception) {
        $errorreference = substr(
            hash(
                'sha256',
                $paymentid
                . '|' . $payment->get_provider()
                . '|' . $exception::class
                . '|' . $exception->getMessage()
            ),
            0,
            12
        );

        error_log(
            '[commerce][refund][' . $errorreference . '] payment='
            . $paymentid
            . ' provider=' . $payment->get_provider()
            . ' exception=' . $exception::class
            . ' message=' . $exception->getMessage()
        );

        redirect(
            $pageurl,
            get_string(
                'commerce_refund_failed',
                'local_subscriptions',
                $exception->getMessage()
                    . ' [ref:' . $errorreference . ']'
            ),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => get_string(
            'commerce_purchases_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/index.php'
        ),
    ],
    [
        'label' => $purchase->summary->publicreference
            ?: $purchase->summary->reference,
        'url' => $returnurl,
    ],
    [
        'label' => $title,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_refund_page_help',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PURCHASES
);

$summaryrows = [
    [
        get_string(
            'commerce_purchase_provider',
            'local_subscriptions'
        ),
        Provider::label_with_icon($payment->get_provider()),
    ],
    [
        get_string(
            'commerce_refund_payment_amount',
            'local_subscriptions'
        ),
        CommercePurchasePresentation::money(
            $payment->get_amount_minor(),
            $payment->get_currency()
        ),
    ],
    [
        get_string(
            'commerce_refund_already_refunded',
            'local_subscriptions'
        ),
        CommercePurchasePresentation::money(
            $payment->get_amount_minor() - $remainingminor,
            $payment->get_currency()
        ),
    ],
    [
        get_string(
            'commerce_refund_remaining',
            'local_subscriptions'
        ),
        html_writer::tag(
            'strong',
            CommercePurchasePresentation::money(
                $remainingminor,
                $payment->get_currency()
            )
        ),
    ],
];

$dl = html_writer::start_tag(
    'dl',
    ['class' => 'row mb-0']
);
foreach ($summaryrows as [$label, $value]) {
    $dl .= html_writer::tag(
        'dt',
        s($label),
        ['class' => 'col-sm-5 text-muted']
    );
    $dl .= html_writer::tag(
        'dd',
        $value,
        ['class' => 'col-sm-7']
    );
}
$dl .= html_writer::end_tag('dl');

echo CommerceDesignSystemRenderer::panel(
    get_string(
        'commerce_refund_summary_title',
        'local_subscriptions'
    ),
    $dl,
    'mt-3'
);

$form = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'mt-3',
]);

foreach ([
    'id' => $purchaseid,
    'paymentid' => $paymentid,
    'sesskey' => sesskey(),
    'refundtoken' => $token,
] as $name => $value) {
    $form .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => $name,
        'value' => $value,
    ]);
}

$form .= html_writer::start_div('mb-3');
$form .= html_writer::tag(
    'label',
    get_string(
        'commerce_refund_amount',
        'local_subscriptions'
    ),
    [
        'for' => 'commerce-refund-amount',
        'class' => 'form-label fw-semibold',
    ]
);
$form .= html_writer::empty_tag('input', [
    'id' => 'commerce-refund-amount',
    'name' => 'amount',
    'type' => 'text',
    'inputmode' => 'decimal',
    'required' => 'required',
    'class' => 'form-control',
    'value' => CommerceCurrencyAmount::major_input_from_minor(
        $remainingminor,
        $payment->get_currency()
    ),
]);
$form .= html_writer::div(
    get_string(
        'commerce_refund_amount_help',
        'local_subscriptions',
        CommercePurchasePresentation::money(
            $remainingminor,
            $payment->get_currency()
        )
    ),
    'form-text'
);
$form .= html_writer::end_div();

$form .= html_writer::start_div('mb-3');
$form .= html_writer::tag(
    'label',
    get_string(
        'commerce_refund_reason',
        'local_subscriptions'
    ),
    [
        'for' => 'commerce-refund-reason',
        'class' => 'form-label fw-semibold',
    ]
);
$form .= html_writer::select(
    [
        'requested_by_customer' =>
            get_string(
                'commerce_refund_reason_customer',
                'local_subscriptions'
            ),
        'duplicate' =>
            get_string(
                'commerce_refund_reason_duplicate',
                'local_subscriptions'
            ),
        'fraudulent' =>
            get_string(
                'commerce_refund_reason_fraudulent',
                'local_subscriptions'
            ),
        'other' =>
            get_string(
                'commerce_refund_reason_other',
                'local_subscriptions'
            ),
    ],
    'reason',
    'requested_by_customer',
    false,
    [
        'id' => 'commerce-refund-reason',
        'class' => 'form-select',
    ]
);
$form .= html_writer::end_div();

if ($revocable !== []) {
    $rightitems = '';
    foreach ($revocable as $right) {
        $rightitems .= html_writer::tag(
            'li',
            s($right['productsku'] . ' — ' . $right['type'])
        );
    }

    $form .= html_writer::div(
        html_writer::checkbox(
            'revokeaccess',
            '1',
            false,
            '',
            [
                'id' => 'commerce-refund-revoke-access',
                'class' => 'form-check-input',
            ]
        )
        . html_writer::tag(
            'label',
            get_string(
                'commerce_refund_revoke_rights',
                'local_subscriptions'
            ),
            [
                'for' => 'commerce-refund-revoke-access',
                'class' => 'form-check-label ms-2 fw-semibold',
            ]
        )
        . html_writer::div(
            get_string(
                'commerce_refund_revoke_rights_help',
                'local_subscriptions'
            )
            . html_writer::tag('ul', $rightitems, ['class' => 'mb-0 mt-2']),
            'form-text ms-4'
        ),
        'form-check mb-3'
    );
}

$form .= html_writer::div(
    html_writer::checkbox(
        'confirmrefund',
        '1',
        false,
        '',
        [
            'id' => 'commerce-refund-confirm',
            'class' => 'form-check-input',
            'required' => 'required',
        ]
    )
    . html_writer::tag(
        'label',
        get_string(
            'commerce_refund_confirmation',
            'local_subscriptions'
        ),
        [
            'for' => 'commerce-refund-confirm',
            'class' => 'form-check-label ms-2',
        ]
    ),
    'form-check mb-4'
);

$form .= html_writer::start_div(
    'd-flex flex-wrap gap-2'
);
$form .= html_writer::tag(
    'button',
    get_string(
        'commerce_refund_submit',
        'local_subscriptions'
    ),
    [
        'type' => 'submit',
        'class' => 'btn btn-danger',
    ]
);
$form .= html_writer::link(
    $returnurl,
    get_string('cancel'),
    ['class' => 'btn btn-outline-secondary']
);
$form .= html_writer::end_div();
$form .= html_writer::end_tag('form');

echo CommerceDesignSystemRenderer::panel(
    get_string(
        'commerce_refund_form_title',
        'local_subscriptions'
    ),
    $form,
    'mt-4'
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
