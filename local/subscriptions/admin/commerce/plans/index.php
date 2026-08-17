<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommercePlanStatusToggleRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url(subscription_config::commerce_plans_page());
$title = get_string('commerce_plans_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-plans-page');

$plans = $DB->get_records_sql(
    "SELECT p.*, s.name AS scopename,
            (SELECT COUNT(1)
               FROM {user_subscription} us
              WHERE us.planid = p.id
                AND us.status IN ('active', 'queued')) AS currentsubscriptions,
            np.id AS nativeproductid,
            np.name AS nativeproductname,
            np.sku AS nativeproductsku,
            np.status AS nativeproductstatus
       FROM {subscription_plan} p
  LEFT JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
  LEFT JOIN {local_subs_commerce_prod_map} pm
         ON pm.legacytable = 'subscription_plan'
        AND pm.legacyid = p.id
  LEFT JOIN {local_subs_commerce_product} np ON np.id = pm.productid
   ORDER BY p.name ASC"
);
$durations = subscription_config::get_plans();

$headeractions = html_writer::link(
    new moodle_url(subscription_config::commerce_plan_edit_page()),
    html_writer::tag('i', '', ['class' => 'fa fa-plus me-1', 'aria-hidden' => 'true'])
        . get_string('commerce_plan_add', 'local_subscriptions'),
    ['class' => 'btn btn-primary']
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_plans_description_n106', 'local_subscriptions'),
    HelpContext::COMMERCE,
    $headeractions
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

$table = new html_table();
$table->attributes['class'] = 'generaltable table table-hover align-middle mb-0 commerce-plans-table';
$table->head = [
    get_string('planname', 'local_subscriptions'),
    get_string('scopes', 'local_subscriptions'),
    get_string('planduration', 'local_subscriptions'),
    get_string('commerce_plan_current_subscriptions', 'local_subscriptions'),
    get_string('commerce_plan_native_product', 'local_subscriptions'),
    get_string('active'),
    get_string('actions'),
];
foreach ($plans as $plan) {
    $viewurl = new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $plan->id]);
    $toggle = CommercePlanStatusToggleRenderer::render((int)$plan->id, (bool)$plan->is_active, $pageurl);
    $actions = html_writer::link(
        $viewurl,
        html_writer::tag('i', '', ['class' => 'fa fa-eye me-1', 'aria-hidden' => 'true']) . get_string('view'),
        ['class' => 'btn btn-sm btn-outline-secondary me-1']
    );
    $actions .= html_writer::link(
        new moodle_url(subscription_config::commerce_plan_edit_page(), ['id' => $plan->id]),
        html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true']) . get_string('edit'),
        ['class' => 'btn btn-sm btn-outline-primary me-1']
    );
    if ((int)$plan->currentsubscriptions === 0) {
        $actions .= html_writer::link(
            new moodle_url(subscription_config::commerce_plan_delete_page(), ['id' => $plan->id, 'sesskey' => sesskey()]),
            html_writer::tag('i', '', ['class' => 'fa fa-trash me-1', 'aria-hidden' => 'true']) . get_string('delete'),
            ['class' => 'btn btn-sm btn-outline-danger']
        );
    }

    $scopehtml = $plan->scopename
        ? html_writer::link(
            new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $plan->accessscopeid]),
            html_writer::span('🎓', 'me-1', ['aria-hidden' => 'true']) . format_string($plan->scopename),
            ['class' => 'text-decoration-none']
        )
        : html_writer::span(get_string('none'), 'text-muted');

    if (!empty($plan->nativeproductid)) {
        $producturl = new moodle_url('/local/subscriptions/admin/commerce/products/view.php', [
            'origin' => 'native',
            'id' => (int)$plan->nativeproductid,
        ]);
        $nativehtml = html_writer::link(
            $producturl,
            html_writer::span('NATIVE', 'badge rounded-pill text-bg-success me-1')
                . html_writer::span(format_string($plan->nativeproductname), 'fw-semibold'),
            ['class' => 'commerce-plan-native-product text-decoration-none']
        );
        $nativehtml .= html_writer::div(s($plan->nativeproductsku), 'small text-muted mt-1');
    } else {
        $nativehtml = html_writer::span(
            get_string('commerce_plan_no_native_product', 'local_subscriptions'),
            'badge rounded-pill bg-light text-dark border'
        );
    }

    $table->data[] = [
        html_writer::link($viewurl, format_string($plan->name), ['class' => 'fw-semibold text-decoration-none']),
        $scopehtml,
        html_writer::span('⌛', 'me-1', ['aria-hidden' => 'true']) . s($durations[$plan->duration_key] ?? $plan->duration_key),
        html_writer::span((string)(int)$plan->currentsubscriptions, 'badge rounded-pill bg-light text-dark border'),
        $nativehtml,
        $toggle,
        html_writer::div($actions, 'commerce-plan-row-actions'),
    ];
}

echo html_writer::start_div('card commerce-plans-list-card');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_plans_list_title', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_plans_list_description', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-plans-card-header'
);
if ($plans) {
    echo html_writer::div(html_writer::table($table), 'table-responsive');
} else {
    echo html_writer::div(get_string('commerce_plans_empty', 'local_subscriptions'), 'card-body text-muted');
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
