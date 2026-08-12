<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentitySearchService;
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
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(200, max(25, optional_param('perpage', 100, PARAM_INT)));
$criteria = [
    'q' => trim(optional_param('q', '', PARAM_RAW_TRIMMED)),
    'email' => trim(optional_param('email', '', PARAM_RAW_TRIMMED)),
    'name' => trim(optional_param('name', '', PARAM_RAW_TRIMMED)),
    'reference' => trim(optional_param('reference', '', PARAM_RAW_TRIMMED)),
    'sku' => trim(optional_param('sku', '', PARAM_RAW_TRIMMED)),
    'purchaseid' => max(0, optional_param('purchaseid', 0, PARAM_INT)),
    'candidateuserid' => max(0, optional_param('candidateuserid', 0, PARAM_INT)),
    'status' => trim(optional_param('status', '', PARAM_ALPHAEXT)),
];
$allowedstatuses = ['', CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED,
    CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND,
    CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS,
    CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED];
if (!in_array($criteria['status'], $allowedstatuses, true)) {
    $criteria['status'] = '';
}
$params = ['page' => $page, 'perpage' => $perpage];
foreach ($criteria as $key => $value) {
    if ($value !== '' && $value !== 0) {
        $params[$key] = $value;
    }
}
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php', $params);
$title = get_string('commerce_identity_reconciliation_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-customer-identities-page');

$reconciliation = new CommerceCustomerIdentityReconciliationService($DB);
$search = new CommerceCustomerIdentitySearchService($DB, $reconciliation);
$searchresult = $search->search($criteria, $page * $perpage, $perpage);
$total = $searchresult['total'];
$items = $searchresult['items'];

$statuslabel = static function(string $status): string {
    $map = [
        CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED => ['commerce_identity_status_matched', 'badge bg-success'],
        CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND => ['commerce_identity_status_not_found', 'badge bg-secondary'],
        CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS => ['commerce_identity_status_ambiguous', 'badge bg-warning text-dark'],
        CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED => ['commerce_identity_status_skipped', 'badge bg-light text-dark'],
    ];
    [$key, $class] = $map[$status] ?? ['commerce_identity_status_skipped', 'badge bg-light text-dark'];
    return html_writer::span(get_string($key, 'local_subscriptions'), $class);
};

$filterurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php');
$returnurl = $pageurl->out(false);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_identity_reconciliation_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::IDENTITIES, $context);
echo CommerceCustomerIdentityNavigationRenderer::render(
    CommerceCustomerIdentityNavigationRenderer::RECONCILIATION
);
echo html_writer::div(get_string('commerce_identity_reconciliation_dryrun_notice', 'local_subscriptions'), 'alert alert-info');

// Advanced filters.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out(false), 'class' => 'card card-body mb-4']);
echo html_writer::start_div('row g-3');
$fields = [
    ['q', 'commerce_identity_filter_any', 'text'],
    ['email', 'commerce_identity_filter_email_partial', 'text'],
    ['name', 'commerce_identity_filter_name', 'text'],
    ['reference', 'commerce_identity_filter_reference', 'text'],
    ['sku', 'commerce_identity_filter_sku', 'text'],
    ['purchaseid', 'commerce_identity_filter_purchaseid', 'number'],
    ['candidateuserid', 'commerce_identity_filter_candidateuserid', 'number'],
];
foreach ($fields as [$name, $key, $type]) {
    echo html_writer::start_div('col-12 col-md-4');
    echo html_writer::tag('label', get_string($key, 'local_subscriptions'), ['for' => 'identity-' . $name, 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'identity-' . $name, 'type' => $type, 'name' => $name, 'value' => $criteria[$name] ?: '', 'class' => 'form-control']);
    echo html_writer::end_div();
}
echo html_writer::start_div('col-12 col-md-4');
echo html_writer::tag('label', get_string('commerce_identity_filter_status', 'local_subscriptions'), ['for' => 'identity-status', 'class' => 'form-label']);
$options = ['' => get_string('all')];
foreach ([CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED, CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND, CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS, CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED] as $status) {
    $options[$status] = get_string('commerce_identity_status_' . $status, 'local_subscriptions');
}
echo html_writer::select($options, 'status', $criteria['status'], false, ['id' => 'identity-status', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('d-flex gap-2 mt-3');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'perpage', 'value' => $perpage]);
echo html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::link($filterurl, get_string('reset'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::div(get_string('commerce_identity_results_count', 'local_subscriptions', $total), 'small text-muted mb-3');

if ($items === []) {
    echo html_writer::div(get_string('commerce_identity_reconciliation_empty', 'local_subscriptions'), 'alert alert-success');
} else {
    $canmanage = has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context);
    if ($canmanage) {
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/subscriptions/admin/commerce/customer-identities/bulk.php'))->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'preview']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'returnurl', 'value' => $returnurl]);
    }
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        $canmanage ? get_string('commerce_identity_select', 'local_subscriptions') : '',
        get_string('commerce_identity_purchase', 'local_subscriptions'),
        get_string('commerce_identity_customer', 'local_subscriptions'),
        get_string('commerce_identity_email', 'local_subscriptions'),
        get_string('commerce_identity_diagnostic', 'local_subscriptions'),
        get_string('commerce_identity_candidate', 'local_subscriptions'),
        get_string('commerce_identity_dryrun_impact', 'local_subscriptions'),
    ];
    foreach ($items as $item) {
        $purchase = $item['purchase'];
        $preview = $item['preview'];
        $result = $preview->result;
        $publicreference = (new CommercePublicOrderReference())->from_internal((string)$purchase->reference, (int)$purchase->timecreated);
        $purchaselink = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $purchase->id]), s($publicreference))
            . html_writer::div(s((string)$purchase->reference) . ' · #' . (int)$purchase->id, 'small text-muted');
        $customername = CommercePersonalOfferCrmPresentation::customer_name_from_purchase($purchase);
        $candidate = '-';
        $candidateids = $result->userid !== null ? [$result->userid] : $result->candidateuserids;
        if ($candidateids !== []) {
            $links=[];
            foreach ($candidateids as $candidateuserid) {
                $u=$DB->get_record('user',['id'=>(int)$candidateuserid],'id,firstname,lastname,email',IGNORE_MISSING);
                $label=$u ? CommercePersonalOfferCrmPresentation::customer_name_from_user($u) : '';
                $links[]=html_writer::link(new moodle_url('/local/subscriptions/admin/users/view.php',['id'=>(int)$candidateuserid]), s(($label !== '' ? $label . ' ' : '') . '#' . (int)$candidateuserid));
            }
            $candidate=implode(', ',$links);
        }
        $select='';
        if ($canmanage && $result->status === CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED) {
            $select=html_writer::empty_tag('input',['type'=>'checkbox','name'=>'purchaseids[]','value'=>(int)$purchase->id,'class'=>'form-check-input','aria-label'=>get_string('commerce_identity_select_purchase','local_subscriptions',$publicreference)]);
        }
        $impact = $result->status === CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED
            ? get_string('commerce_identity_dryrun_impact_summary', 'local_subscriptions', (object)[
                'total' => $preview->total_changes(), 'grants' => $preview->grantsupdated,
                'digital' => $preview->digitalaccessupdated, 'guests' => $preview->guestsessionsupdated,
                'legacy' => $preview->legacyrecordsupdated,
            ]) : '—';
        $table->data[] = [$select, $purchaselink, s($customername !== '' ? $customername : '—'), s($result->email ?? '-'), $statuslabel($result->status), $candidate, s($impact)];
    }
    echo html_writer::table($table);
    if ($canmanage) {
        echo html_writer::tag('button', get_string('commerce_identity_bulk_preview', 'local_subscriptions'), ['type'=>'submit','class'=>'btn btn-primary']);
        echo html_writer::end_tag('form');
    }
    if ($total > $perpage) {
        $pagingbase = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php', array_filter($params, static fn($v,$k) => $k !== 'page', ARRAY_FILTER_USE_BOTH));
        echo $OUTPUT->paging_bar($total, $page, $perpage, $pagingbase, 'page');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
