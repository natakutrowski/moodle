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
$id = required_param('id', PARAM_INT);
$scope = $DB->get_record('subscription_access_scope', ['id' => $id], '*', MUST_EXIST);
$pageurl = new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $id]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, (string)$scope->name, 'local-subscriptions-commerce-scope-view-page');
$title = format_string($scope->name);

$courseids = array_filter(array_map('intval', explode(',', (string)$scope->course_ids)));
$courses = $courseids ? $DB->get_records_list('course', 'id', $courseids, 'fullname ASC', 'id,fullname') : [];
$plans = $DB->get_records('subscription_plan', ['accessscopeid' => $id], 'name ASC');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('commerce_scopes_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_access_scopes_page())],
    ['label' => $title, 'url' => null],
]);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo $OUTPUT->heading($title);
$actions = html_writer::link(new moodle_url(subscription_config::commerce_access_scope_edit_page(), ['id' => $id]), get_string('edit'), ['class' => 'btn btn-primary me-2']);
if ($plans === []) {
    $actions .= html_writer::link(new moodle_url(subscription_config::commerce_access_scope_delete_page(), ['id' => $id, 'sesskey' => sesskey()]), get_string('delete'), ['class' => 'btn btn-outline-danger me-2']);
}
$actions .= html_writer::link(new moodle_url(subscription_config::commerce_access_scopes_page()), get_string('back'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::div($actions, 'mb-4');

echo html_writer::tag('h3', get_string('includedcourses', 'local_subscriptions'), ['class' => 'h5']);
$items = [];
foreach ($courses as $course) {
    $items[] = html_writer::tag('li', format_string($course->fullname));
}
echo $items ? html_writer::tag('ul', implode('', $items)) : html_writer::div(get_string('none'), 'text-muted');

echo html_writer::tag('h3', get_string('commerce_scope_used_by_plans', 'local_subscriptions'), ['class' => 'h5 mt-4']);
$items = [];
foreach ($plans as $plan) {
    $items[] = html_writer::tag('li', html_writer::link(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $plan->id]), format_string($plan->name)));
}
echo $items ? html_writer::tag('ul', implode('', $items)) : html_writer::div(get_string('none'), 'text-muted');

$technical = new html_table();
$technical->caption = get_string('commerce_technical_information', 'local_subscriptions');
$technical->data = [
    [get_string('commerce_internal_id', 'local_subscriptions'), (int)$scope->id],
    [get_string('includedcourses', 'local_subscriptions'), count($courseids)],
    [get_string('commerce_scope_plans_count', 'local_subscriptions'), count($plans)],
    [get_string('commerce_date_created', 'local_subscriptions'), $scope->creation_date ? userdate((int)$scope->creation_date) : get_string('unknown')],
    [get_string('commerce_date_modified', 'local_subscriptions'), $scope->last_update ? userdate((int)$scope->last_update) : get_string('unknown')],
];
echo html_writer::table($technical);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
