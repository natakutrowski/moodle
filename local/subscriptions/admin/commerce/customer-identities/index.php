<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$email = trim(optional_param('email', '', PARAM_EMAIL));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(200, max(25, optional_param('perpage', 100, PARAM_INT)));

$params = ['page' => $page, 'perpage' => $perpage];
if ($email !== '') {
    $params['email'] = $email;
}
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php', $params);
$title = get_string('commerce_identity_reconciliation_title', 'local_subscriptions');
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-customer-identities-page'
);

$service = new CommerceCustomerIdentityReconciliationService($DB);
$total = $service->count_unresolved($email !== '' ? $email : null);
$offset = $page * $perpage;
$results = $service->reconcile_batch($perpage, false, $email !== '' ? $email : null, $offset);

$counts = [
    CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED => 0,
];
foreach ($results as $result) {
    if (array_key_exists($result->status, $counts)) {
        $counts[$result->status]++;
    }
}

$statuslabel = static function(string $status): string {
    $map = [
        CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED => ['commerce_identity_status_matched', 'badge bg-success'],
        CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND => ['commerce_identity_status_not_found', 'badge bg-secondary'],
        CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS => ['commerce_identity_status_ambiguous', 'badge bg-warning text-dark'],
        CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED => ['commerce_identity_status_skipped', 'badge bg-light text-dark'],
        CommerceCustomerIdentityReconciliationResult::STATUS_UNCHANGED => ['commerce_identity_status_unchanged', 'badge bg-info text-dark'],
        CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED => ['commerce_identity_status_reconciled', 'badge bg-primary'],
    ];
    [$key, $class] = $map[$status] ?? ['commerce_identity_status_skipped', 'badge bg-light text-dark'];
    return html_writer::span(get_string($key, 'local_subscriptions'), $class);
};

$summarycard = static function(string $label, string $value, string $class = ''): string {
    return html_writer::div(
        html_writer::div(s($label), 'small text-muted') .
        html_writer::div(s($value), 'h4 mb-0 ' . $class),
        'border rounded p-3 bg-white'
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_identity_reconciliation_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::IDENTITIES, $context);

echo html_writer::div(
    get_string('commerce_identity_reconciliation_dryrun_notice', 'local_subscriptions'),
    'alert alert-info'
);

echo html_writer::start_div('row g-3 mb-4');
echo html_writer::div($summarycard(
    get_string('commerce_identity_unresolved_total', 'local_subscriptions'),
    (string)$total
), 'col-12 col-md-3');
echo html_writer::div($summarycard(
    get_string('commerce_identity_matched_on_page', 'local_subscriptions'),
    (string)$counts[CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED],
    'text-success'
), 'col-12 col-md-3');
echo html_writer::div($summarycard(
    get_string('commerce_identity_not_found_on_page', 'local_subscriptions'),
    (string)$counts[CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND]
), 'col-12 col-md-3');
echo html_writer::div($summarycard(
    get_string('commerce_identity_ambiguous_on_page', 'local_subscriptions'),
    (string)$counts[CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS],
    'text-warning'
), 'col-12 col-md-3');
echo html_writer::end_div();

$filterurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out(false), 'class' => 'row g-2 align-items-end mb-4']);
echo html_writer::start_div('col-12 col-md-6');
echo html_writer::tag('label', get_string('commerce_identity_filter_email', 'local_subscriptions'), ['for' => 'commerce-identity-email', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'id' => 'commerce-identity-email',
    'type' => 'email',
    'name' => 'email',
    'value' => $email,
    'class' => 'form-control',
    'placeholder' => 'client@example.com',
]);
echo html_writer::end_div();
echo html_writer::start_div('col-auto');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'perpage', 'value' => $perpage]);
echo html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_div();
if ($email !== '') {
    echo html_writer::div(
        html_writer::link($filterurl, get_string('reset'), ['class' => 'btn btn-outline-secondary']),
        'col-auto'
    );
}
echo html_writer::end_tag('form');

if ($results === []) {
    echo html_writer::div(get_string('commerce_identity_reconciliation_empty', 'local_subscriptions'), 'alert alert-success');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        get_string('commerce_identity_purchase', 'local_subscriptions'),
        get_string('commerce_identity_customer', 'local_subscriptions'),
        get_string('commerce_identity_email', 'local_subscriptions'),
        get_string('commerce_identity_diagnostic', 'local_subscriptions'),
        get_string('commerce_identity_candidate', 'local_subscriptions'),
        get_string('actions'),
    ];

    foreach ($results as $result) {
        $purchaserecord = $result->purchaseid !== null
            ? $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $result->purchaseid], 'id,reference,timecreated,customerjson', IGNORE_MISSING)
            : null;
        $purchase = '-';
        if ($purchaserecord) {
            $publicreference = (new CommercePublicOrderReference())->from_internal((string)$purchaserecord->reference, (int)$purchaserecord->timecreated);
            $purchase = html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $result->purchaseid]),
                s($publicreference)
            ) . html_writer::div(s((string)$purchaserecord->reference) . ' · #' . $result->purchaseid, 'small text-muted');
        }
        $customername = CommercePersonalOfferCrmPresentation::customer_name_from_purchase($purchaserecord);

        $candidate = '-';
        if ($result->userid !== null) {
            $candidateuser = $DB->get_record('user', ['id' => $result->userid], 'id,firstname,lastname,email', IGNORE_MISSING);
            $label = $candidateuser ? CommercePersonalOfferCrmPresentation::customer_name_from_user($candidateuser) : '';
            $label = $label !== '' ? $label . ' (#' . $result->userid . ')' : get_string('commerce_identity_user_link', 'local_subscriptions', $result->userid);
            $candidate = html_writer::link(new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $result->userid]), s($label));
        } else if ($result->candidateuserids !== []) {
            $links = [];
            foreach ($result->candidateuserids as $candidateuserid) {
                $candidateuser = $DB->get_record('user', ['id' => (int)$candidateuserid], 'id,firstname,lastname,email', IGNORE_MISSING);
                $label = $candidateuser ? CommercePersonalOfferCrmPresentation::customer_name_from_user($candidateuser) : '';
                $links[] = html_writer::link(
                    new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => (int)$candidateuserid]),
                    s(($label !== '' ? $label . ' ' : '') . '#' . (int)$candidateuserid)
                );
            }
            $candidate = implode(', ', $links);
        }

        $actions = '';
        if (
            $result->status === CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED &&
            $result->purchaseid !== null &&
            has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
        ) {
            $executeurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/action.php', [
                'id' => $result->purchaseid,
                'action' => 'reconcile',
                'sesskey' => sesskey(),
            ]);
            $actions = html_writer::link(
                $executeurl,
                get_string('commerce_identity_reconcile_action', 'local_subscriptions'),
                [
                    'class' => 'btn btn-sm btn-outline-danger',
                    'data-confirmation' => 'modal',
                    'data-confirmation-title-str' => json_encode(['commerce_identity_reconcile_action', 'local_subscriptions']),
                    'data-confirmation-content-str' => json_encode(['commerce_identity_reconcile_confirm', 'local_subscriptions']),
                    'data-confirmation-yes-button-str' => json_encode(['yes']),
                    'data-confirmation-destination' => $executeurl->out(false),
                ]
            );
        }

        $table->data[] = [
            $purchase,
            s($customername !== '' ? $customername : '—'),
            s($result->email ?? '-'),
            $statuslabel($result->status),
            $candidate,
            $actions,
        ];
    }

    echo html_writer::table($table);

    if ($total > $perpage) {
        echo $OUTPUT->paging_bar($total, $page, $perpage, $filterurl, 'page');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
