<?php

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_form.php');

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
$id = optional_param('id', 0, PARAM_INT);
$record = $id ? $DB->get_record('subscription_access_scope', ['id' => $id], '*', MUST_EXIST) : null;
$pageurl = new moodle_url(subscription_config::commerce_access_scope_edit_page(), $id ? ['id' => $id] : []);
$title = $id ? get_string('commerce_scope_edit', 'local_subscriptions') : get_string('commerce_scope_add', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-scope-edit-page');

$form = new access_scope_form($pageurl, $record ? (array)$record : []);
if ($record) {
    $form->set_data(['id' => $record->id, 'name' => $record->name, 'course_ids' => array_filter(array_map('intval', explode(',', (string)$record->course_ids)))]);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url(subscription_config::commerce_access_scopes_page()));
}
if ($data = $form->get_data()) {
    $save = (object)[
        'id' => (int)($data->id ?? 0),
        'name' => trim($data->name),
        'course_ids' => implode(',', array_map('intval', (array)$data->course_ids)),
        'last_update' => time(),
    ];
    if ($save->id) {
        $DB->update_record('subscription_access_scope', $save);
    } else {
        $save->creation_date = time();
        $save->id = $DB->insert_record('subscription_access_scope', $save);
    }
    redirect(new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $save->id]), get_string('changessaved'));
}

$plans = $record ? $DB->get_records('subscription_plan', ['accessscopeid' => $record->id], 'name ASC', 'id,name') : [];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => get_string('commerce_scopes_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_access_scopes_page())],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string($id ? 'commerce_scope_edit_description_n106' : 'commerce_scope_add_description_n106', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

if ($plans) {
    $links = [];
    foreach ($plans as $plan) {
        $links[] = html_writer::link(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $plan->id]), format_string($plan->name), ['class' => 'badge rounded-pill bg-light text-dark border text-decoration-none']);
    }
    echo html_writer::div(
        html_writer::div('🔗', 'commerce-scope-impact-icon') .
        html_writer::div(
            html_writer::tag('strong', get_string('commerce_scope_dependency_title', 'local_subscriptions', count($plans))) .
            html_writer::tag('p', get_string('commerce_scope_dependency_help', 'local_subscriptions'), ['class' => 'mb-2 text-muted']) .
            html_writer::div(implode(' ', $links), 'd-flex flex-wrap gap-1'),
            'flex-grow-1'
        ),
        'commerce-scope-impact d-flex gap-3 align-items-start mb-3'
    );
}

echo html_writer::start_div('card commerce-scope-form-card');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_scope_general_title', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_scope_general_description', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-scope-form-card-header'
);
echo html_writer::start_div('commerce-scope-form-card-body');
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
