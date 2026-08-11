<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommercePlanStatusToggleRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url(subscription_config::commerce_plans_page());
$title = get_string('commerce_plans_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-plans-page');
$plans = $DB->get_records_sql("SELECT p.*, s.name AS scopename,
        (SELECT COUNT(1) FROM {user_subscription} us WHERE us.planid = p.id AND us.status IN ('active', 'queued')) AS currentsubscriptions
    FROM {subscription_plan} p
    LEFT JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
    ORDER BY p.name ASC");
$durations = subscription_config::get_plans();

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_configuration_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo $OUTPUT->heading($title);
echo html_writer::div(html_writer::link(new moodle_url(subscription_config::commerce_plan_edit_page()), get_string('commerce_plan_add', 'local_subscriptions'), ['class' => 'btn btn-primary']), 'mb-3');
$table = new html_table();
$table->head = [
    get_string('planname', 'local_subscriptions'),
    get_string('scopes', 'local_subscriptions'),
    get_string('planduration', 'local_subscriptions'),
    get_string('commerce_plan_current_subscriptions', 'local_subscriptions'),
    get_string('active'),
    get_string('actions'),
];
foreach ($plans as $plan) {
    $viewurl = new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $plan->id]);
    $toggle = CommercePlanStatusToggleRenderer::render(
        (int)$plan->id,
        (bool)$plan->is_active,
        $pageurl
    );
    $actions = html_writer::link($viewurl, get_string('view'), ['class' => 'btn btn-sm btn-outline-secondary me-1']);
    $actions .= html_writer::link(new moodle_url(subscription_config::commerce_plan_edit_page(), ['id' => $plan->id]), get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary me-1']);
    if ((int)$plan->currentsubscriptions === 0) {
        $actions .= html_writer::link(new moodle_url(subscription_config::commerce_plan_delete_page(), ['id' => $plan->id, 'sesskey' => sesskey()]), get_string('delete'), ['class' => 'btn btn-sm btn-outline-danger']);
    }
    $table->data[] = [
        html_writer::link($viewurl, format_string($plan->name)),
        $plan->scopename ? html_writer::link(new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $plan->accessscopeid]), format_string($plan->scopename)) : get_string('none'),
        s($durations[$plan->duration_key] ?? $plan->duration_key),
        (int)$plan->currentsubscriptions,
        $toggle,
        $actions,
    ];
}
echo html_writer::table($table);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
