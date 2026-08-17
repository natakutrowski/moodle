<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url(subscription_config::commerce_access_scopes_page());
$title = get_string('commerce_scopes_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-scopes-page');

$scopes = $DB->get_records('subscription_access_scope', null, 'name ASC');
$plancounts = [];
if ($scopes) {
    $ids = array_keys($scopes);
    [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'scope');
    $rows = $DB->get_records_sql("SELECT accessscopeid, COUNT(1) AS total FROM {subscription_plan} WHERE accessscopeid $insql GROUP BY accessscopeid", $params);
    foreach ($rows as $row) {
        $plancounts[(int)$row->accessscopeid] = (int)$row->total;
    }
}

$addurl = new moodle_url(subscription_config::commerce_access_scope_edit_page());
$addbutton = html_writer::link($addurl, '＋ ' . get_string('commerce_scope_add', 'local_subscriptions'), ['class' => 'btn btn-primary']);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_scopes_description_n106', 'local_subscriptions'), HelpContext::COMMERCE, $addbutton);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

echo html_writer::start_div('card commerce-scope-list-card');
echo html_writer::start_div('table-responsive');
$table = new html_table();
$table->attributes['class'] = 'table align-middle mb-0 commerce-scope-table';
$table->head = [
    get_string('scopename', 'local_subscriptions'),
    get_string('includedcourses', 'local_subscriptions'),
    get_string('commerce_scope_plans_count', 'local_subscriptions'),
    get_string('actions'),
];
foreach ($scopes as $scope) {
    $courses = array_filter(array_map('intval', explode(',', (string)$scope->course_ids)));
    $plancount = $plancounts[(int)$scope->id] ?? 0;
    $viewurl = new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $scope->id]);
    $editurl = new moodle_url(subscription_config::commerce_access_scope_edit_page(), ['id' => $scope->id]);
    $actions = html_writer::link($viewurl, '👁 ' . get_string('view'), ['class' => 'btn btn-sm btn-outline-secondary me-1']);
    $actions .= html_writer::link($editurl, '✎ ' . get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary me-1']);
    if ($plancount === 0) {
        $actions .= html_writer::link(new moodle_url(subscription_config::commerce_access_scope_delete_page(), ['id' => $scope->id, 'sesskey' => sesskey()]), html_writer::tag('i', '', ['class' => 'fa fa-trash me-1', 'aria-hidden' => 'true']) . get_string('delete'), ['class' => 'btn btn-sm btn-outline-danger']);
    }
    $table->data[] = [
        html_writer::link($viewurl, format_string($scope->name), ['class' => 'fw-semibold commerce-scope-name']),
        html_writer::span(get_string('commerce_scope_courses_badge', 'local_subscriptions', count($courses)), 'badge rounded-pill bg-light text-dark border'),
        html_writer::span(get_string('commerce_scope_plans_badge', 'local_subscriptions', $plancount), 'badge rounded-pill ' . ($plancount ? 'commerce-scope-plan-badge' : 'bg-light text-muted border')),
        html_writer::div($actions, 'text-nowrap'),
    ];
}
echo html_writer::table($table);
echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
