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
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    (string)$plan->name,
    'local-subscriptions-commerce-plan-view-page'
);

$title = format_string($plan->name);
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

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('commerce_plans_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::commerce_plans_page()),
    ],
    ['label' => $title, 'url' => null],
]);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::CONFIGURATION,
    $context
);

echo html_writer::start_div('d-flex flex-wrap justify-content-between align-items-center mb-3');
echo $OUTPUT->heading($title, 2, 'mb-0');
echo CommercePlanStatusToggleRenderer::render((int)$plan->id, (bool)$plan->is_active, $pageurl);
echo html_writer::end_div();

$actions = [
    html_writer::link(
        new moodle_url(subscription_config::commerce_plan_edit_page(), ['id' => $id]),
        get_string('edit'),
        ['class' => 'btn btn-primary me-2 mb-2']
    ),
    html_writer::link(
        new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $id]),
        get_string('planentitlements', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary me-2 mb-2']
    ),
    html_writer::link(
        new moodle_url(subscription_config::plan_upgrades_page(), ['planid' => $id]),
        get_string('planupgrades', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary me-2 mb-2']
    ),
];
if ($current === 0) {
    $actions[] = html_writer::link(
        new moodle_url(subscription_config::commerce_plan_delete_page(), [
            'id' => $id,
            'sesskey' => sesskey(),
        ]),
        get_string('delete'),
        ['class' => 'btn btn-outline-danger me-2 mb-2']
    );
}
$actions[] = html_writer::link(
    new moodle_url(subscription_config::commerce_plans_page()),
    get_string('back'),
    ['class' => 'btn btn-outline-secondary mb-2']
);
echo html_writer::div(implode('', $actions), 'mb-3');

$table = new html_table();
$table->caption = get_string('commerce_plan_business_information', 'local_subscriptions');
$table->data = [
    [
        get_string('scopes', 'local_subscriptions'),
        $plan->accessscopeid
            ? html_writer::link(
                new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $plan->accessscopeid]),
                format_string($plan->scopename)
            )
            : get_string('none'),
    ],
    [
        get_string('planduration', 'local_subscriptions'),
        s($durations[$plan->duration_key] ?? $plan->duration_key),
    ],
    [
        get_string('is_recurring', 'local_subscriptions'),
        $plan->is_recurring ? get_string('yes') : get_string('no'),
    ],
    [
        get_string('commerce_plan_current_subscriptions', 'local_subscriptions'),
        $current,
    ],
];
echo html_writer::table($table);

// Entitlements remain the execution-level rules historically attached to a Plan.
echo html_writer::start_div('card mb-4');
echo html_writer::div(
    html_writer::tag('h3', get_string('planentitlements', 'local_subscriptions'), ['class' => 'h5 mb-0']),
    'card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::div(
    get_string('commerce_plan_entitlements_explanation', 'local_subscriptions'),
    'alert alert-info'
);
if ($entitlements) {
    $entitlementtable = new html_table();
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
            format_string($entitlement->coursename),
            get_string($accesskey, 'local_subscriptions'),
            s($entitlement->roleshortname),
            $entitlement->groupname ? s($entitlement->groupname) : get_string('none'),
            (int)$entitlement->priority,
        ];
    }
    echo html_writer::table($entitlementtable);
} else {
    echo $OUTPUT->notification(
        get_string('noentitlements', 'local_subscriptions'),
        \core\output\notification::NOTIFY_INFO
    );
}
echo html_writer::link(
    new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $id]),
    get_string('commerce_manage_entitlements', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary mt-3']
);
echo html_writer::end_div();
echo html_writer::end_div();

// Upgrade rules involving this Plan, both as source and as destination.
echo html_writer::start_div('card mb-4');
echo html_writer::div(
    html_writer::tag('h3', get_string('planupgrades', 'local_subscriptions'), ['class' => 'h5 mb-0']),
    'card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::div(
    get_string('commerce_plan_upgrades_explanation', 'local_subscriptions'),
    'alert alert-info'
);
if ($upgrades) {
    $upgradetable = new html_table();
    $upgradetable->head = [
        get_string('upgrade_fromplan', 'local_subscriptions'),
        get_string('upgrade_toplan', 'local_subscriptions'),
        get_string('upgrade_pricingmode', 'local_subscriptions'),
        get_string('status', 'local_subscriptions'),
    ];
    foreach ($upgrades as $upgrade) {
        $pricingkey = 'upgrade_pricing_' . $upgrade->pricingmode;
        $upgradetable->data[] = [
            format_string($upgrade->fromplanname),
            format_string($upgrade->toplanname),
            get_string($pricingkey, 'local_subscriptions'),
            !empty($upgrade->isactive)
                ? html_writer::span(get_string('active', 'local_subscriptions'), 'badge bg-success')
                : html_writer::span(get_string('inactive', 'local_subscriptions'), 'badge bg-secondary'),
        ];
    }
    echo html_writer::table($upgradetable);
} else {
    echo $OUTPUT->notification(
        get_string('noupgrades', 'local_subscriptions'),
        \core\output\notification::NOTIFY_INFO
    );
}
echo html_writer::link(
    new moodle_url(subscription_config::plan_upgrades_page(), ['planid' => $id]),
    get_string('commerce_manage_upgrades', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary mt-3']
);
echo html_writer::end_div();
echo html_writer::end_div();

$technical = new html_table();
$technical->caption = get_string('commerce_technical_information', 'local_subscriptions');
$technical->data = [
    [get_string('commerce_internal_id', 'local_subscriptions'), (int)$plan->id],
    [get_string('active'), $plan->is_active ? get_string('yes') : get_string('no')],
    [
        get_string('commerce_date_created', 'local_subscriptions'),
        $plan->creation_date ? userdate((int)$plan->creation_date) : get_string('unknown'),
    ],
    [
        get_string('commerce_date_modified', 'local_subscriptions'),
        $plan->last_update ? userdate((int)$plan->last_update) : get_string('unknown'),
    ],
];
echo html_writer::table($technical);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
