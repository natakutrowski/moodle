<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\payment\Provider;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$id = required_param('id', PARAM_INT);
$execute = optional_param('execute', 0, PARAM_BOOL);

$repository = new CommercePurchaseReadRepository($DB);
$purchase = $repository->find_by_id($id);
if ($purchase === null) {
    throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions');
}
$summary = $purchase->summary;
if ($summary->provider !== Provider::ALFA) {
    throw new moodle_exception('commerce_alfa_reconciliation_wrong_provider', 'local_subscriptions');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php', ['id' => $id]);
$purchaseurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $id]);
$pagetitle = get_string('commerce_alfa_crm_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-alfa-reconciliation-page');

$service = AlfaPaymentReconciliationService::create($DB);
$error = null;
$inspection = null;

try {
    $inspection = $service->inspect_purchase_reference($summary->reference);
    if ($execute) {
        require_sesskey();
        require_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context);
        if (!$inspection->reconcilable && !$inspection->alreadycomplete) {
            throw new moodle_exception(
                'commerce_alfa_reconciliation_not_safe',
                'local_subscriptions',
                '',
                implode(', ', $inspection->blockers)
            );
        }
        if (!$inspection->alreadycomplete) {
            $inspection = $service->reconcile_payment($inspection->paymentid);
        }
        redirect(
            new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', [
                'id' => $id,
                'alfa_reconciled' => 1,
            ]),
            get_string('commerce_alfa_crm_success', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
} catch (\Throwable $exception) {
    if ($execute) {
        $error = $exception;
    } else {
        $error = $exception;
    }
}

$yesno = static fn(bool $value): string => $value
    ? html_writer::span(get_string('yes'), 'badge bg-success')
    : html_writer::span(get_string('no'), 'badge bg-danger');
$statusbadge = static function(string $label, bool $ok): string {
    return html_writer::span(s($label), $ok ? 'badge bg-success' : 'badge bg-warning text-dark');
};
$definition = static function(array $rows): string {
    $html = html_writer::start_tag('dl', ['class' => 'row mb-0']);
    foreach ($rows as [$label, $value]) {
        $html .= html_writer::tag('dt', s($label), ['class' => 'col-sm-5 text-muted'])
            . html_writer::tag('dd', $value, ['class' => 'col-sm-7']);
    }
    return $html . html_writer::end_tag('dl');
};

$blockerlabel = static function(string $blocker): string {
    $key = 'commerce_alfa_reconciliation_blocker_' . $blocker;
    return get_string_manager()->string_exists($key, 'local_subscriptions')
        ? get_string($key, 'local_subscriptions')
        : $blocker;
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_purchases_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php')],
    ['label' => $summary->publicreference !== '' ? $summary->publicreference : $summary->reference, 'url' => $purchaseurl],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render(
    $pagetitle,
    get_string('commerce_alfa_crm_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);

echo html_writer::div(
    html_writer::span('ALFA', 'badge bg-primary me-2')
        . s(get_string('commerce_alfa_crm_live_warning', 'local_subscriptions')),
    'alert alert-info'
);

if ($error !== null) {
    echo html_writer::div(
        s(get_string('commerce_alfa_crm_provider_error', 'local_subscriptions', $error->getMessage())),
        'alert alert-danger'
    );
    echo html_writer::link($purchaseurl, get_string('back'), ['class' => 'btn btn-outline-secondary']);
    echo CrmWorkspaceRenderer::end();
    echo $OUTPUT->footer();
    exit;
}

if ($inspection === null) {
    throw new coding_exception('Alfa reconciliation inspection was not produced.');
}

$providerpaid = $inspection->providerpaid;
$allmatches = $inspection->amountmatches
    && $inspection->currencymatches
    && $inspection->approvedamountmatches
    && $inspection->depositedamountmatches;

$stateclass = $inspection->alreadycomplete
    ? 'border-success'
    : ($inspection->reconcilable ? 'border-success' : 'border-warning');
$statetitle = $inspection->alreadycomplete
    ? get_string('commerce_alfa_crm_state_complete', 'local_subscriptions')
    : ($inspection->reconcilable
        ? get_string('commerce_alfa_crm_state_reconcilable', 'local_subscriptions')
        : get_string('commerce_alfa_crm_state_blocked', 'local_subscriptions'));
$statedetail = $inspection->alreadycomplete
    ? get_string('commerce_alfa_crm_state_complete_help', 'local_subscriptions')
    : ($inspection->reconcilable
        ? get_string('commerce_alfa_crm_state_reconcilable_help', 'local_subscriptions')
        : get_string('commerce_alfa_crm_state_blocked_help', 'local_subscriptions'));

echo html_writer::start_div('card card-body ' . $stateclass . ' mb-4');
echo html_writer::tag('h3', s($statetitle), ['class' => 'h5 mb-2']);
echo html_writer::div(s($statedetail), 'text-muted');
echo html_writer::end_div();

echo html_writer::start_div('row g-4');
echo html_writer::start_div('col-lg-6');
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_alfa_crm_campus_section', 'local_subscriptions'),
    $definition([
        [get_string('commerce_purchase_internal_reference', 'local_subscriptions'), html_writer::tag('code', s($inspection->purchasereference))],
        [get_string('commerce_alfa_crm_payment_id', 'local_subscriptions'), '#' . $inspection->paymentid],
        [get_string('commerce_purchase_payment_status', 'local_subscriptions'), s($inspection->campuspaymentstatus)],
        [get_string('commerce_purchase_status', 'local_subscriptions'), s($inspection->campuspurchasestatus)],
        [get_string('commerce_purchase_amount', 'local_subscriptions'), CommercePurchasePresentation::money($inspection->campusamountminor, $inspection->campuscurrency)],
    ])
);
echo html_writer::end_div();

echo html_writer::start_div('col-lg-6');
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_alfa_crm_alfa_section', 'local_subscriptions'),
    $definition([
        [get_string('commerce_alfa_crm_order_id', 'local_subscriptions'), html_writer::tag('code', s($inspection->providerorderid))],
        [get_string('commerce_alfa_crm_order_status', 'local_subscriptions'), s((string)($inspection->provider->orderstatus ?? '—'))],
        [get_string('commerce_alfa_crm_payment_state', 'local_subscriptions'), s((string)($inspection->provider->paymentstate ?? '—'))],
        [get_string('commerce_purchase_amount', 'local_subscriptions'), $inspection->provider->amountminor === null ? '—' : CommercePurchasePresentation::money($inspection->provider->amountminor, $inspection->provider->currency ?? $inspection->campuscurrency)],
        [get_string('commerce_alfa_crm_deposited_amount', 'local_subscriptions'), $inspection->provider->depositedamountminor === null ? '—' : CommercePurchasePresentation::money($inspection->provider->depositedamountminor, $inspection->provider->currency ?? $inspection->campuscurrency)],
    ])
);
echo html_writer::end_div();
echo html_writer::end_div();

$checks = [
    [get_string('commerce_alfa_crm_check_provider_paid', 'local_subscriptions'), $inspection->providerpaid],
    [get_string('commerce_alfa_crm_check_amount', 'local_subscriptions'), $inspection->amountmatches],
    [get_string('commerce_alfa_crm_check_currency', 'local_subscriptions'), $inspection->currencymatches],
    [get_string('commerce_alfa_crm_check_approved', 'local_subscriptions'), $inspection->approvedamountmatches],
    [get_string('commerce_alfa_crm_check_deposited', 'local_subscriptions'), $inspection->depositedamountmatches],
];
$checkhtml = html_writer::start_tag('ul', ['class' => 'list-group list-group-flush']);
foreach ($checks as [$label, $ok]) {
    $checkhtml .= html_writer::tag(
        'li',
        html_writer::span($ok ? '✓' : '!', $ok ? 'text-success fw-bold me-2' : 'text-danger fw-bold me-2')
            . s($label)
            . html_writer::span($ok ? get_string('commerce_alfa_crm_check_ok', 'local_subscriptions') : get_string('commerce_alfa_crm_check_failed', 'local_subscriptions'), $ok ? 'badge bg-success float-end' : 'badge bg-danger float-end'),
        ['class' => 'list-group-item px-0']
    );
}
$checkhtml .= html_writer::end_tag('ul');
echo CommerceDesignSystemRenderer::panel(
    get_string('commerce_alfa_crm_checks_section', 'local_subscriptions'),
    $checkhtml,
    'mt-4'
);

if ($inspection->blockers !== []) {
    $items = '';
    foreach ($inspection->blockers as $blocker) {
        $items .= html_writer::tag('li', s($blockerlabel($blocker)));
    }
    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_alfa_crm_blockers', 'local_subscriptions'))
            . html_writer::tag('ul', $items, ['class' => 'mb-0 mt-2']),
        'alert alert-warning mt-4'
    );
}

$actions = html_writer::start_div('d-flex flex-wrap gap-2 mt-4');
$actions .= html_writer::link($purchaseurl, get_string('back'), ['class' => 'btn btn-outline-secondary']);
$actions .= html_writer::link($pageurl, get_string('commerce_alfa_crm_refresh', 'local_subscriptions'), ['class' => 'btn btn-outline-primary']);
if ($inspection->reconcilable && has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
    $executeurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php', [
        'id' => $id,
        'execute' => 1,
        'sesskey' => sesskey(),
    ]);
    $actions .= html_writer::link(
        $executeurl,
        get_string('commerce_alfa_crm_execute', 'local_subscriptions'),
        [
            'class' => 'btn btn-success',
            'data-confirmation' => 'modal',
            'data-confirmation-title-str' => json_encode(['commerce_alfa_crm_execute', 'local_subscriptions']),
            'data-confirmation-content-str' => json_encode(['commerce_alfa_crm_execute_confirm', 'local_subscriptions']),
            'data-confirmation-yes-button-str' => json_encode(['yes']),
            'data-confirmation-destination' => $executeurl->out(false),
        ]
    );
}
$actions .= html_writer::end_div();
echo $actions;

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
