<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
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

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_configuration_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo $OUTPUT->heading($title);
echo html_writer::div(
    html_writer::link(new moodle_url(subscription_config::commerce_access_scope_edit_page()), get_string('commerce_scope_add', 'local_subscriptions'), ['class' => 'btn btn-primary']),
    'mb-3'
);

$table = new html_table();
$table->head = [
    get_string('scopename', 'local_subscriptions'),
    get_string('includedcourses', 'local_subscriptions'),
    get_string('commerce_scope_plans_count', 'local_subscriptions'),
    get_string('actions'),
];
foreach ($scopes as $scope) {
    $courses = array_filter(array_map('intval', explode(',', (string)$scope->course_ids)));
    $plancount = $DB->count_records('subscription_plan', ['accessscopeid' => $scope->id]);
    $viewurl = new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $scope->id]);
    $actions = html_writer::link($viewurl, get_string('view'), ['class' => 'btn btn-sm btn-outline-secondary me-1']);
    $actions .= html_writer::link(new moodle_url(subscription_config::commerce_access_scope_edit_page(), ['id' => $scope->id]), get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary me-1']);
    if ($plancount === 0) {
        $actions .= html_writer::link(new moodle_url(subscription_config::commerce_access_scope_delete_page(), ['id' => $scope->id, 'sesskey' => sesskey()]), get_string('delete'), ['class' => 'btn btn-sm btn-outline-danger']);
    }
    $table->data[] = [
        html_writer::link($viewurl, format_string($scope->name)),
        count($courses),
        $plancount,
        $actions,
    ];
}
echo html_writer::table($table);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
