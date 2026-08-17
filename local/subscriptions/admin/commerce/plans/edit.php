<?php
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_form.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$id = optional_param('id', 0, PARAM_INT);
$record = $id ? $DB->get_record('subscription_plan', ['id' => $id], '*', MUST_EXIST) : null;
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/plans/edit.php', $id ? ['id' => $id] : []);
$title = $id ? get_string('commerce_plan_edit', 'local_subscriptions') : get_string('commerce_plan_add', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-plan-edit-page');
$form = new plan_form($pageurl, $record ? (array)$record : []);
if ($record) {
    $form->set_data($record);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/subscriptions/admin/commerce/plans/index.php'));
}
if ($data = $form->get_data()) {
    $save = (object)[
        'id' => (int)($data->id ?? 0),
        'name' => trim($data->name),
        'accessscopeid' => (int)$data->accessscopeid,
        'duration_key' => $data->duration_key,
        'highlight_type' => in_array($data->highlight_type ?? '', ['popular', 'premium'], true) ? $data->highlight_type : null,
        'is_recurring' => (int)$data->is_recurring,
        'last_update' => time(),
    ];
    if ($save->id) {
        $DB->update_record('subscription_plan', $save);
    } else {
        $save->creation_date = time();
        $save->is_active = 0;
        $save->id = $DB->insert_record('subscription_plan', $save);
    }
    redirect(new moodle_url('/local/subscriptions/admin/commerce/plans/view.php', ['id' => $save->id]), get_string('changessaved'));
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => get_string('commerce_plans_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/plans/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string($id ? 'commerce_plan_edit_description_n106' : 'commerce_plan_add_description_n106', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

echo html_writer::start_div('card commerce-plan-form-card');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_plan_general_title', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_plan_general_description', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-plans-card-header'
);
echo html_writer::start_div('card-body commerce-plan-form-body');
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
