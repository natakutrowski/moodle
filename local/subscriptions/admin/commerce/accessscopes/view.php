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
// format_string() needs the page context to be available before the title is formatted.
$PAGE->set_context($context);
$id = required_param('id', PARAM_INT);
$scope = $DB->get_record('subscription_access_scope', ['id' => $id], '*', MUST_EXIST);
$pageurl = new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $id]);
$title = format_string($scope->name);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-scope-view-page');

$courseids = array_filter(array_map('intval', explode(',', (string)$scope->course_ids)));
$courses = $courseids ? $DB->get_records_list('course', 'id', $courseids, 'fullname ASC', 'id,fullname') : [];
$plans = $DB->get_records('subscription_plan', ['accessscopeid' => $id], 'name ASC');

$editurl = new moodle_url(subscription_config::commerce_access_scope_edit_page(), ['id' => $id]);
$actions = html_writer::link(
    $editurl,
    html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true']) . get_string('edit'),
    ['class' => 'btn btn-primary']
);
if ($plans === []) {
    $actions .= html_writer::link(
        new moodle_url(subscription_config::commerce_access_scope_delete_page(), ['id' => $id, 'sesskey' => sesskey()]),
        html_writer::tag('i', '', ['class' => 'fa fa-trash me-1', 'aria-hidden' => 'true']) . get_string('delete'),
        ['class' => 'btn btn-outline-danger ms-2']
    );
}
$actions .= html_writer::link(
    new moodle_url(subscription_config::commerce_access_scopes_page()),
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left me-1', 'aria-hidden' => 'true']) . get_string('back'),
    ['class' => 'btn btn-outline-secondary ms-2']
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => get_string('commerce_scopes_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_access_scopes_page())],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_scope_view_description_n106', 'local_subscriptions'),
    HelpContext::COMMERCE,
    $actions
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

echo html_writer::start_div('row g-3 commerce-scope-view-grid');
echo html_writer::start_div('col-12 col-xl-7');
echo html_writer::start_div('card commerce-scope-view-card h-100');
echo html_writer::div(
    html_writer::tag('h2', get_string('includedcourses', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_scope_view_courses_description', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-scope-view-card-header'
);
echo html_writer::start_div('commerce-scope-view-card-body');
if ($courses) {
    foreach ($courses as $course) {
        echo html_writer::div(
            html_writer::span('🎓', 'commerce-scope-view-item-icon', ['aria-hidden' => 'true']) .
            html_writer::link(
                new moodle_url('/course/view.php', ['id' => $course->id]),
                format_string($course->fullname),
                ['class' => 'fw-semibold text-decoration-none']
            ),
            'commerce-scope-view-item'
        );
    }
} else {
    echo html_writer::div(get_string('none'), 'text-muted');
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-xl-5');
echo html_writer::start_div('card commerce-scope-view-card h-100');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_scope_used_by_plans', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_scope_view_plans_description', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-scope-view-card-header'
);
echo html_writer::start_div('commerce-scope-view-card-body');
if ($plans) {
    foreach ($plans as $plan) {
        echo html_writer::link(
            new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $plan->id]),
            html_writer::span('📦', 'commerce-scope-view-item-icon', ['aria-hidden' => 'true']) .
            html_writer::span(format_string($plan->name), 'fw-semibold'),
            ['class' => 'commerce-scope-view-item commerce-scope-view-plan text-decoration-none']
        );
    }
} else {
    echo html_writer::div(get_string('commerce_scope_view_no_plans', 'local_subscriptions'), 'text-muted');
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

$technical = new html_table();
$technical->attributes['class'] = 'table mb-0 commerce-scope-technical-table';
$technical->data = [
    [get_string('commerce_internal_id', 'local_subscriptions'), '#' . (int)$scope->id],
    [get_string('includedcourses', 'local_subscriptions'), html_writer::span((string)count($courseids), 'badge rounded-pill bg-light text-dark border')],
    [get_string('commerce_scope_plans_count', 'local_subscriptions'), html_writer::span((string)count($plans), 'badge rounded-pill bg-light text-dark border')],
    [get_string('commerce_date_created', 'local_subscriptions'), $scope->creation_date ? userdate((int)$scope->creation_date) : get_string('unknown')],
    [get_string('commerce_date_modified', 'local_subscriptions'), $scope->last_update ? userdate((int)$scope->last_update) : get_string('unknown')],
];
echo html_writer::start_div('card commerce-scope-technical-card mt-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_technical_information', 'local_subscriptions'), ['class' => 'h5 mb-0']),
    'commerce-scope-view-card-header'
);
echo html_writer::div(html_writer::table($technical), 'table-responsive');
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
