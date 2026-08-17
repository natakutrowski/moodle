<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_entitlement_form.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$PAGE->set_context($context);

$planid = optional_param('planid', 0, PARAM_INT);
$add = optional_param('add', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

if ($planid <= 0 || !$plan = $DB->get_record('subscription_plan', ['id' => $planid])) {
    redirect(
        new moodle_url(subscription_config::commerce_plans_page()),
        get_string('invalidplanid', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$pageurl = new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid]);
$pagetitle = get_string('planentitlementsfor', 'local_subscriptions', format_string($plan->name));
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-plan-entitlements-page');

$formurl = new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid]);
$mform = new plan_entitlement_form($formurl, ['planid' => $planid]);

if ($mform->is_cancelled()) {
    redirect($pageurl);
}
if ($data = $mform->get_data()) {
    $now = time();
    $record = (object)[
        'planid' => $planid,
        'courseid' => (int)$data->courseid,
        'accesslevel' => trim($data->accesslevel),
        'roleshortname' => trim($data->roleshortname),
        'groupname' => trim($data->groupname ?? ''),
        'priority' => (int)($data->priority ?? 100),
        'lastupdate' => $now,
    ];
    if (!empty($data->id)) {
        $record->id = (int)$data->id;
        $DB->update_record('subscription_plan_entitlement', $record);
        redirect($pageurl, get_string('entitlementupdated', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    $record->timecreated = $now;
    $DB->insert_record('subscription_plan_entitlement', $record);
    redirect($pageurl, get_string('entitlementcreated', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}
if ($delete) {
    require_sesskey();
    $entitlement = $DB->get_record('subscription_plan_entitlement', ['id' => $delete, 'planid' => $planid], '*', MUST_EXIST);
    $DB->delete_records('subscription_plan_entitlement', ['id' => $entitlement->id]);
    redirect($pageurl, get_string('entitlementdeleted', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$entitlements = $DB->get_records_sql(
    "SELECT e.*, c.fullname AS coursename
       FROM {subscription_plan_entitlement} e
       JOIN {course} c ON c.id = e.courseid
      WHERE e.planid = :planid
   ORDER BY e.priority DESC, c.fullname ASC, e.accesslevel ASC",
    ['planid' => $planid]
);

$headeractions = '';
if (!$add && !$edit) {
    $headeractions = html_writer::link(
        new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid, 'add' => 1]),
        html_writer::tag('i', '', ['class' => 'fa fa-plus me-1', 'aria-hidden' => 'true']) . get_string('addentitlement', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::admin_commerce_page())],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => get_string('commerce_plans_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_plans_page())],
    ['label' => format_string($plan->name), 'url' => new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid])],
    ['label' => get_string('planentitlements', 'local_subscriptions'), 'url' => null],
]);
echo CrmPageHeader::render(
    $pagetitle,
    get_string('crm_plan_entitlements_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

echo html_writer::div(
    html_writer::div(
        html_writer::span(get_string('commerce_plans_title', 'local_subscriptions'), 'commerce-plan-context-label') .
        html_writer::link(
            new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid]),
            format_string($plan->name),
            ['class' => 'commerce-plan-context-name text-decoration-none']
        )
    ) .
    html_writer::link(
        new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid]),
        html_writer::tag('i', '', ['class' => 'fa fa-arrow-left me-1', 'aria-hidden' => 'true']) .
        get_string('commerce_back_to_plan', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary']
    ),
    'commerce-plan-rules-context'
);

echo html_writer::start_div('card commerce-plan-rules-card');
echo html_writer::div(
    html_writer::div(
        html_writer::tag('h2', get_string('commerce_plan_entitlements_rules_title', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
        html_writer::tag('p', get_string('commerce_plan_entitlements_rules_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0'])
    ) .
    ($headeractions !== '' ? html_writer::div($headeractions, 'commerce-plan-rules-header-actions') : ''),
    'commerce-plans-card-header commerce-plan-rules-header'
);
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-info-circle me-2', 'aria-hidden' => 'true']) .
    get_string('commerce_plan_entitlements_explanation', 'local_subscriptions'),
    'commerce-plan-subtle-note commerce-plan-rules-note'
);
if ($entitlements) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle mb-0';
    $table->head = [
        get_string('entitlement_course', 'local_subscriptions'),
        get_string('entitlement_accesslevel', 'local_subscriptions'),
        get_string('entitlement_role', 'local_subscriptions'),
        get_string('entitlement_groupname', 'local_subscriptions'),
        get_string('entitlement_priority', 'local_subscriptions'),
        get_string('actions', 'local_subscriptions'),
    ];
    foreach ($entitlements as $e) {
        $editurl = new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid, 'edit' => $e->id]);
        $deleteurl = new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid, 'delete' => $e->id, 'sesskey' => sesskey()]);
        $actions = html_writer::link(
            $editurl,
            html_writer::tag('i', '', ['class' => 'fa fa-pencil', 'aria-hidden' => 'true']),
            ['class' => 'btn btn-sm btn-outline-primary me-1', 'title' => get_string('editentitlement', 'local_subscriptions')]
        );
        $actions .= html_writer::link(
            $deleteurl,
            html_writer::tag('i', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true']),
            [
                'class' => 'btn btn-sm btn-outline-danger',
                'title' => get_string('deleteentitlement', 'local_subscriptions'),
                'onclick' => 'return confirm(' . json_encode(get_string('confirmdeleteentitlement', 'local_subscriptions')) . ');',
            ]
        );
        $table->data[] = [
            html_writer::link(new moodle_url('/course/view.php', ['id' => $e->courseid]), format_string($e->coursename), ['class' => 'fw-semibold text-decoration-none']),
            get_string('accesslevel_' . $e->accesslevel, 'local_subscriptions'),
            html_writer::span(s($e->roleshortname), 'badge rounded-pill bg-light text-dark border'),
            $e->groupname ? s($e->groupname) : html_writer::span(get_string('none', 'local_subscriptions'), 'text-muted'),
            (int)$e->priority,
            $actions,
        ];
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive');
} else {
    echo html_writer::div(get_string('noentitlements', 'local_subscriptions'), 'card-body text-muted');
}
echo html_writer::end_div();

if ($edit || $add) {
    if ($edit) {
        $entitlement = $DB->get_record('subscription_plan_entitlement', ['id' => $edit, 'planid' => $planid], '*', MUST_EXIST);
        $mform->set_data($entitlement);
        $formtitle = get_string('editentitlement', 'local_subscriptions');
    } else {
        $mform->set_data((object)['planid' => $planid, 'priority' => 100]);
        $formtitle = get_string('addentitlement', 'local_subscriptions');
    }
    echo html_writer::start_div('card commerce-plan-rule-form-card mt-3');
    echo html_writer::div(html_writer::tag('h2', $formtitle, ['class' => 'h5 mb-0']), 'commerce-plans-card-header');
    echo html_writer::start_div('card-body commerce-plan-rule-form-body');
    $mform->display();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
