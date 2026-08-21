<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\quality\CommerceEmailQualityService;
use local_subscriptions\commerce\customer\quality\CommerceLegacyDigitalIdentityService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$q = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$qualitystatus = trim(optional_param('quality', 'suspect', PARAM_ALPHA));
if (!in_array($qualitystatus, ['', CommerceEmailQualityService::STATUS_OK, CommerceEmailQualityService::STATUS_INVALID, CommerceEmailQualityService::STATUS_SUSPECT], true)) {
    $qualitystatus = 'suspect';
}
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 50;

$params = array_filter(['q' => $q, 'quality' => $qualitystatus, 'page' => $page], static fn($v) => $v !== '' && $v !== 0);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/legacy-quality.php', $params);
$title = get_string('commerce_identity_legacy_quality_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-customer-identities-legacy-quality-page');

$service = new CommerceLegacyDigitalIdentityService($DB, new CommerceEmailQualityService());
$result = $service->search($q, $qualitystatus, $page * $perpage, $perpage);
$canmanage = has_capability(Capabilities::MANAGE_USERS, $context);

$statusbadge = static function(string $status): string {
    $map = [
        CommerceEmailQualityService::STATUS_OK => ['commerce_identity_email_quality_ok', 'badge bg-success'],
        CommerceEmailQualityService::STATUS_SUSPECT => ['commerce_identity_email_quality_suspect', 'badge bg-warning text-dark'],
        CommerceEmailQualityService::STATUS_INVALID => ['commerce_identity_email_quality_invalid', 'badge bg-danger'],
    ];
    [$key, $class] = $map[$status] ?? $map[CommerceEmailQualityService::STATUS_OK];
    return html_writer::span(get_string($key, 'local_subscriptions'), $class);
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_identity_reconciliation_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_identity_legacy_quality_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::IDENTITIES, $context);
echo CommerceCustomerIdentityNavigationRenderer::render(CommerceCustomerIdentityNavigationRenderer::LEGACY_QUALITY);
echo html_writer::div(get_string('commerce_identity_legacy_quality_notice', 'local_subscriptions'), 'alert alert-info');

$filterurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/legacy-quality.php');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out(false), 'class' => 'card card-body mb-4 crm-identity-filter-card']);
echo html_writer::start_div('row g-3 align-items-end');
echo html_writer::start_div('col-12 col-md-6');
echo html_writer::tag('label', get_string('commerce_identity_legacy_quality_search', 'local_subscriptions'), ['for' => 'legacy-quality-q', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'legacy-quality-q', 'name' => 'q', 'type' => 'text', 'value' => $q, 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::start_div('col-12 col-md-3');
echo html_writer::tag('label', get_string('commerce_identity_legacy_quality_filter', 'local_subscriptions'), ['for' => 'legacy-quality-status', 'class' => 'form-label']);
echo html_writer::select([
    '' => get_string('all'),
    CommerceEmailQualityService::STATUS_SUSPECT => get_string('commerce_identity_email_quality_suspect', 'local_subscriptions'),
    CommerceEmailQualityService::STATUS_INVALID => get_string('commerce_identity_email_quality_invalid', 'local_subscriptions'),
    CommerceEmailQualityService::STATUS_OK => get_string('commerce_identity_email_quality_ok', 'local_subscriptions'),
], 'quality', $qualitystatus, false, ['id' => 'legacy-quality-status', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::start_div('col-12 col-md-3 d-flex gap-2');
echo html_writer::tag(
    'button',
    html_writer::tag('i', '', [
        'class' => 'fa fa-filter me-1',
        'aria-hidden' => 'true',
    ]) . get_string('commerce_filters_apply', 'local_subscriptions'),
    ['type' => 'submit', 'class' => 'btn btn-primary']
);
echo html_writer::link($filterurl, get_string('reset'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::div(
    html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'crm_identity_legacy_quality_results_title',
                'local_subscriptions',
                $result['total']
            )
        )
        . html_writer::span(
            get_string(
                'crm_identity_legacy_quality_results_help',
                'local_subscriptions'
            ),
            'crm-identity-legacy-quality-results-help'
        ),
        'crm-identity-legacy-quality-results-copy'
    ),
    'crm-identity-legacy-quality-results-bar'
);

if ($result['items'] === []) {
    echo html_writer::div(get_string('commerce_identity_legacy_quality_empty', 'local_subscriptions'), 'alert alert-success');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle crm-identity-table';
    $table->head = [
        get_string('commerce_identity_legacy_quality_customer', 'local_subscriptions'),
        get_string('commerce_identity_email', 'local_subscriptions'),
        get_string('commerce_identity_legacy_quality_diagnostic', 'local_subscriptions'),
        get_string(
            'crm_identity_legacy_quality_history',
            'local_subscriptions'
        ),
        get_string('actions'),
    ];
    foreach ($result['items'] as $item) {
        $record = $item['record'];
        $diagnostic = $item['diagnostic'];
        $name = trim((string)$record->firstname . ' ' . (string)$record->lastname);
        $emailcell = html_writer::tag('strong', s((string)$record->email));
        if ($diagnostic->suggestion !== null) {
            $emailcell .= html_writer::div(
                get_string('commerce_identity_legacy_quality_suggestion', 'local_subscriptions', $diagnostic->suggestion),
                'small text-warning-emphasis mt-1'
            );
        }
        $purchase = html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_identity_legacy_quality_purchase_count',
                    'local_subscriptions',
                    (int)$item['purchasecount']
                )
            )
            . html_writer::div(
                get_string(
                    'commerce_identity_legacy_quality_latest_purchase',
                    'local_subscriptions',
                    (int)$record->id
                ),
                'crm-identity-legacy-quality-history-meta'
            ),
            'crm-identity-legacy-quality-history'
        );

        $customer = s($name !== '' ? $name : '—');
        if (!empty($record->userid)) {
            $customer = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/users/view.php',
                    ['id' => (int)$record->userid]
                ),
                s($name !== '' ? $name : '—'),
                [
                    'class' =>
                        'crm-identity-legacy-quality-customer-link',
                ]
            )
            . html_writer::div(
                'Moodle #' . (int)$record->userid,
                'crm-identity-legacy-quality-customer-meta'
            );
        }

        $actions = '—';
        if ($canmanage) {
            $editurl = new moodle_url(
                '/local/subscriptions/admin/commerce/customer-identities/legacy-edit.php',
                [
                    'id' => (int)$record->id,
                    'returnurl' => $pageurl->out(false),
                ]
            );
            $actions = html_writer::link(
                $editurl,
                html_writer::tag('i', '', [
                    'class' => 'fa fa-pencil',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string(
                        'crm_identity_legacy_quality_correct',
                        'local_subscriptions'
                    )
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-primary '
                        . 'crm-identity-legacy-quality-action',
                ]
            );
        }

        $rowclass = '';
        if (
            $diagnostic->status ===
            CommerceEmailQualityService::STATUS_INVALID
        ) {
            $rowclass = 'crm-identity-legacy-quality-row-invalid';
        } elseif (
            $diagnostic->status ===
            CommerceEmailQualityService::STATUS_SUSPECT
        ) {
            $rowclass = 'crm-identity-legacy-quality-row-suspect';
        }

        $table->data[] = [
            $customer,
            $emailcell,
            $statusbadge($diagnostic->status),
            $purchase,
            $actions,
        ];

        if ($rowclass !== '') {
            $lastrow = count($table->data) - 1;
            $table->rowclasses[$lastrow] = $rowclass;
        }
    }
    echo html_writer::table($table);
    if ($result['total'] > $perpage) {
        $paging = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/legacy-quality.php', array_filter(['q' => $q, 'quality' => $qualitystatus], static fn($v) => $v !== ''));
        echo $OUTPUT->paging_bar($result['total'], $page, $perpage, $paging, 'page');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
