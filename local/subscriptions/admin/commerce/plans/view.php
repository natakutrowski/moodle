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
$PAGE->set_context($context);
$id = required_param('id', PARAM_INT);
$plan = $DB->get_record_sql(
    "SELECT p.*, s.name AS scopename
       FROM {subscription_plan} p
  LEFT JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
      WHERE p.id = :id",
    ['id' => $id],
    MUST_EXIST
);

$pageurl = new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $id]);
$title = format_string($plan->name);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-plan-view-page');

$durations = subscription_config::get_plans();
$current = $DB->count_records_select(
    'user_subscription',
    'planid = :planid AND status IN (:active, :queued)',
    ['planid' => $id, 'active' => 'active', 'queued' => 'queued']
);

$entitlements = $DB->get_records_sql(
    "SELECT e.*, c.fullname AS coursename
       FROM {subscription_plan_entitlement} e
       JOIN {course} c ON c.id = e.courseid
      WHERE e.planid = :planid
   ORDER BY e.priority DESC, c.fullname ASC, e.accesslevel ASC",
    ['planid' => $id]
);

$upgrades = $DB->get_records_sql(
    "SELECT u.*, fp.name AS fromplanname, tp.name AS toplanname
       FROM {subscription_plan_upgrade} u
       JOIN {subscription_plan} fp ON fp.id = u.fromplanid
       JOIN {subscription_plan} tp ON tp.id = u.toplanid
      WHERE u.fromplanid = :fromplanid OR u.toplanid = :toplanid
   ORDER BY fp.name ASC, tp.name ASC",
    ['fromplanid' => $id, 'toplanid' => $id]
);

$nativeproduct = $DB->get_record_sql(
    "SELECT p.id, p.name, p.sku, p.type, p.status
       FROM {local_subs_commerce_prod_map} m
       JOIN {local_subs_commerce_product} p ON p.id = m.productid
      WHERE m.legacytable = :legacytable
        AND m.legacyid = :legacyid",
    ['legacytable' => 'subscription_plan', 'legacyid' => $id],
    IGNORE_MISSING
);

$headeractions = html_writer::link(
    new moodle_url(subscription_config::commerce_plan_edit_page(), ['id' => $id]),
    html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true']) . get_string('edit'),
    ['class' => 'btn btn-primary']
);
$headeractions .= html_writer::link(
    new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $id]),
    html_writer::tag('i', '', ['class' => 'fa fa-key me-1', 'aria-hidden' => 'true']) . get_string('planentitlements', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary ms-2']
);
$headeractions .= html_writer::link(
    new moodle_url(subscription_config::plan_upgrades_page(), ['planid' => $id]),
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-up me-1', 'aria-hidden' => 'true']) . get_string('planupgrades', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary ms-2']
);
if ($current === 0) {
    $headeractions .= html_writer::link(
        new moodle_url(subscription_config::commerce_plan_delete_page(), ['id' => $id, 'sesskey' => sesskey()]),
        html_writer::tag('i', '', ['class' => 'fa fa-trash me-1', 'aria-hidden' => 'true']) . get_string('delete'),
        ['class' => 'btn btn-outline-danger ms-2']
    );
}
$headeractions .= html_writer::link(
    new moodle_url(subscription_config::commerce_plans_page()),
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left me-1', 'aria-hidden' => 'true']) . get_string('back'),
    ['class' => 'btn btn-outline-secondary ms-2']
);

$statushtml = CommercePlanStatusToggleRenderer::render((int)$plan->id, (bool)$plan->is_active, $pageurl);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => get_string('commerce_plans_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_plans_page())],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_plan_view_description_n106', 'local_subscriptions'),
    HelpContext::COMMERCE,
    $headeractions
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

echo html_writer::start_div('row g-3 commerce-plan-view-grid');
echo html_writer::start_div('col-12 col-xl-7');
echo html_writer::start_div('card commerce-plan-view-card h-100');
echo html_writer::div(
    html_writer::div(
        html_writer::tag('h2', get_string('commerce_plan_business_information', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
        html_writer::tag('p', get_string('commerce_plan_business_information_desc_n106', 'local_subscriptions'), ['class' => 'text-muted mb-0'])
    ) .
    html_writer::div($statushtml, 'commerce-plan-business-status'),
    'commerce-plans-card-header commerce-plan-business-header'
);
$business = new html_table();
$business->attributes['class'] = 'table mb-0 commerce-plan-detail-table';
$business->data = [
    [
        get_string('scopes', 'local_subscriptions'),
        $plan->accessscopeid
            ? html_writer::link(
                new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $plan->accessscopeid]),
                html_writer::span('🎓', 'me-1', ['aria-hidden' => 'true']) . format_string($plan->scopename),
                ['class' => 'fw-semibold text-decoration-none']
            )
            : html_writer::span(get_string('none'), 'text-muted'),
    ],
    [get_string('planduration', 'local_subscriptions'), html_writer::span('⌛', 'me-1', ['aria-hidden' => 'true']) . s($durations[$plan->duration_key] ?? $plan->duration_key)],
    [get_string('is_recurring', 'local_subscriptions'), $plan->is_recurring ? html_writer::span(get_string('yes'), 'badge rounded-pill text-bg-success') : html_writer::span(get_string('no'), 'badge rounded-pill bg-light text-dark border')],
    [get_string('commerce_plan_current_subscriptions', 'local_subscriptions'), html_writer::span((string)$current, 'badge rounded-pill bg-light text-dark border')],
];
echo html_writer::div(html_writer::table($business), 'table-responsive');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-xl-5');
echo html_writer::start_div('card commerce-plan-view-card h-100');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_plan_native_product', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_plan_native_product_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-plans-card-header'
);
echo html_writer::start_div('card-body');
if ($nativeproduct) {
    $producturl = new moodle_url('/local/subscriptions/admin/commerce/products/view.php', [
        'origin' => 'native',
        'id' => (int)$nativeproduct->id,
    ]);
    echo html_writer::div(
        html_writer::span('NATIVE', 'badge rounded-pill text-bg-success me-2') .
        html_writer::span(s(strtoupper((string)$nativeproduct->status)), 'badge rounded-pill bg-light text-dark border'),
        'mb-3'
    );
    echo html_writer::link($producturl, format_string($nativeproduct->name), ['class' => 'h5 d-block text-decoration-none mb-1']);
    echo html_writer::div(s($nativeproduct->sku), 'text-muted small mb-3');
    echo html_writer::link(
        $producturl,
        html_writer::tag('i', '', ['class' => 'fa fa-box me-1', 'aria-hidden' => 'true']) . get_string('commerce_plan_open_native_product', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary btn-sm']
    );
} else {
    echo html_writer::div(
        html_writer::span('○', 'commerce-plan-native-empty-icon', ['aria-hidden' => 'true']) .
        html_writer::tag('strong', get_string('commerce_plan_no_native_product', 'local_subscriptions')) .
        html_writer::tag('p', get_string('commerce_plan_no_native_product_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0 mt-1']),
        'commerce-plan-native-empty'
    );
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Execution-level entitlement rules.
echo html_writer::start_div('card commerce-plan-view-card mt-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('planentitlements', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_plan_entitlements_explanation', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-plans-card-header d-flex justify-content-between align-items-start'
);
echo html_writer::start_div('card-body pt-2');
if ($entitlements) {
    $entitlementtable = new html_table();
    $entitlementtable->attributes['class'] = 'table table-hover align-middle mb-3';
    $entitlementtable->head = [
        get_string('entitlement_course', 'local_subscriptions'),
        get_string('entitlement_accesslevel', 'local_subscriptions'),
        get_string('entitlement_role', 'local_subscriptions'),
        get_string('entitlement_groupname', 'local_subscriptions'),
        get_string('entitlement_priority', 'local_subscriptions'),
    ];
    foreach ($entitlements as $entitlement) {
        $accesskey = 'accesslevel_' . $entitlement->accesslevel;
        $entitlementtable->data[] = [
            html_writer::link(new moodle_url('/course/view.php', ['id' => $entitlement->courseid]), format_string($entitlement->coursename), ['class' => 'fw-semibold text-decoration-none']),
            get_string($accesskey, 'local_subscriptions'),
            html_writer::span(s($entitlement->roleshortname), 'badge rounded-pill bg-light text-dark border'),
            $entitlement->groupname ? s($entitlement->groupname) : html_writer::span(get_string('none'), 'text-muted'),
            (int)$entitlement->priority,
        ];
    }
    echo html_writer::div(html_writer::table($entitlementtable), 'table-responsive');
} else {
    echo html_writer::div(get_string('noentitlements', 'local_subscriptions'), 'text-muted py-2');
}
echo html_writer::link(
    new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $id]),
    html_writer::tag('i', '', ['class' => 'fa fa-key me-1', 'aria-hidden' => 'true']) . get_string('commerce_manage_entitlements', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary btn-sm']
);
echo html_writer::end_div();
echo html_writer::end_div();

// Upgrade rules involving this Plan, both as source and as destination.
echo html_writer::start_div('card commerce-plan-view-card mt-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('planupgrades', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_plan_upgrades_explanation', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-plans-card-header'
);
echo html_writer::start_div('card-body pt-2');
if ($upgrades) {
    $upgradetable = new html_table();
    $upgradetable->attributes['class'] = 'table table-hover align-middle mb-3';
    $upgradetable->head = [
        get_string('upgrade_fromplan', 'local_subscriptions'),
        get_string('upgrade_toplan', 'local_subscriptions'),
        get_string('upgrade_pricingmode', 'local_subscriptions'),
        get_string('status', 'local_subscriptions'),
    ];
    foreach ($upgrades as $upgrade) {
        $pricingkey = 'upgrade_pricing_' . $upgrade->pricingmode;
        $upgradetable->data[] = [
            html_writer::link(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $upgrade->fromplanid]), format_string($upgrade->fromplanname), ['class' => 'text-decoration-none']),
            html_writer::link(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $upgrade->toplanid]), format_string($upgrade->toplanname), ['class' => 'text-decoration-none']),
            get_string($pricingkey, 'local_subscriptions'),
            !empty($upgrade->isactive)
                ? html_writer::span(get_string('active', 'local_subscriptions'), 'badge rounded-pill text-bg-success')
                : html_writer::span(get_string('inactive', 'local_subscriptions'), 'badge rounded-pill bg-light text-dark border'),
        ];
    }
    echo html_writer::div(html_writer::table($upgradetable), 'table-responsive');
} else {
    echo html_writer::div(get_string('noupgrades', 'local_subscriptions'), 'text-muted py-2');
}
echo html_writer::link(
    new moodle_url(subscription_config::plan_upgrades_page(), ['planid' => $id]),
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-up me-1', 'aria-hidden' => 'true']) . get_string('commerce_manage_upgrades', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary btn-sm']
);
echo html_writer::end_div();
echo html_writer::end_div();

$technical = new html_table();
$technical->attributes['class'] = 'table mb-0 commerce-plan-detail-table';
$technical->data = [
    [get_string('commerce_internal_id', 'local_subscriptions'), '#' . (int)$plan->id],
    [get_string('active'), $plan->is_active ? get_string('yes') : get_string('no')],
    [get_string('commerce_date_created', 'local_subscriptions'), $plan->creation_date ? userdate((int)$plan->creation_date) : get_string('unknown')],
    [get_string('commerce_date_modified', 'local_subscriptions'), $plan->last_update ? userdate((int)$plan->last_update) : get_string('unknown')],
];
echo html_writer::start_div('card commerce-plan-view-card mt-3');
echo html_writer::div(html_writer::tag('h2', get_string('commerce_technical_information', 'local_subscriptions'), ['class' => 'h5 mb-0']), 'commerce-plans-card-header');
echo html_writer::div(html_writer::table($technical), 'table-responsive');
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
