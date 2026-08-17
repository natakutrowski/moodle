<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_upgrade_form.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$PAGE->set_context($context);

$planid = optional_param('planid', 0, PARAM_INT);
$add = optional_param('add', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('del', 0, PARAM_INT);
$plan = $planid ? $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST) : null;

$pageparams = $planid ? ['planid' => $planid] : [];
$pageurl = new moodle_url(subscription_config::plan_upgrades_page(), $pageparams);
$pagetitle = $plan
    ? get_string('commerce_plan_upgrades_for', 'local_subscriptions', s($plan->name))
    : get_string('planupgrades', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-plan-upgrades-page');
if ($plan) {
    $pagetitle = get_string('commerce_plan_upgrades_for', 'local_subscriptions', format_string($plan->name));
}

$mform = new plan_upgrade_form($pageurl);
if ($mform->is_cancelled()) {
    redirect($pageurl);
}
if ($data = $mform->get_data()) {
    $record = (object)[
        'fromplanid' => (int)$data->fromplanid,
        'toplanid' => (int)$data->toplanid,
        'pricingmode' => trim($data->pricingmode),
        'isactive' => (int)$data->isactive,
        'lastupdate' => time(),
    ];
    if (!empty($data->id)) {
        $record->id = (int)$data->id;
        $DB->update_record('subscription_plan_upgrade', $record);
        redirect($pageurl, get_string('upgradeupdated', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    $record->timecreated = time();
    $DB->insert_record('subscription_plan_upgrade', $record);
    redirect($pageurl, get_string('upgradecreated', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}
if ($delete) {
    require_sesskey();
    $conditions = ['id' => $delete];
    $upgrade = $DB->get_record('subscription_plan_upgrade', $conditions, '*', MUST_EXIST);
    if ($planid && (int)$upgrade->fromplanid !== $planid && (int)$upgrade->toplanid !== $planid) {
        throw new moodle_exception('invalidparameter');
    }
    $DB->delete_records('subscription_plan_upgrade', ['id' => $upgrade->id]);
    redirect($pageurl, get_string('upgradedeleted', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$where = '';
$params = [];
if ($planid) {
    $where = ' WHERE u.fromplanid = :fromplanid OR u.toplanid = :toplanid';
    $params = ['fromplanid' => $planid, 'toplanid' => $planid];
}
$upgrades = $DB->get_records_sql(
    "SELECT u.*, fp.name AS fromplanname, tp.name AS toplanname
       FROM {subscription_plan_upgrade} u
       JOIN {subscription_plan} fp ON fp.id = u.fromplanid
       JOIN {subscription_plan} tp ON tp.id = u.toplanid
       {$where}
   ORDER BY fp.name ASC, tp.name ASC",
    $params
);

$headeractions = '';
if (!$add && !$edit) {
    $headeractions = html_writer::link(
        new moodle_url(subscription_config::plan_upgrades_page(), $pageparams + ['add' => 1]),
        html_writer::tag('i', '', ['class' => 'fa fa-plus me-1', 'aria-hidden' => 'true']) . get_string('addupgrade', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
$breadcrumbs = [
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::admin_commerce_page())],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => get_string('commerce_plans_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_plans_page())],
];
if ($plan) {
    $breadcrumbs[] = [
        'label' => format_string($plan->name),
        'url' => new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid]),
    ];
}
$breadcrumbs[] = ['label' => get_string('planupgrades', 'local_subscriptions'), 'url' => null];
echo CrmBreadcrumbRenderer::render($breadcrumbs);
echo CrmPageHeader::render(
    $pagetitle,
    get_string('crm_plan_upgrades_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS, $context);

echo html_writer::div(
    html_writer::div(
        html_writer::span(get_string('commerce_plans_title', 'local_subscriptions'), 'commerce-plan-context-label') .
        ($plan
            ? html_writer::link(
                new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid]),
                format_string($plan->name),
                ['class' => 'commerce-plan-context-name text-decoration-none']
            )
            : html_writer::span(get_string('all'), 'commerce-plan-context-name'))
    ) .
    html_writer::link(
        $plan
            ? new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid])
            : new moodle_url(subscription_config::commerce_plans_page()),
        html_writer::tag('i', '', ['class' => 'fa fa-arrow-left me-1', 'aria-hidden' => 'true']) .
        ($plan ? get_string('commerce_back_to_plan', 'local_subscriptions') : get_string('backtoplanlist', 'local_subscriptions')),
        ['class' => 'btn btn-sm btn-outline-secondary']
    ),
    'commerce-plan-rules-context'
);

echo html_writer::start_div('card commerce-plan-rules-card');
echo html_writer::div(
    html_writer::div(
        html_writer::tag('h2', get_string('commerce_plan_upgrade_rules_title', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
        html_writer::tag('p', get_string('commerce_plan_upgrade_rules_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0'])
    ) .
    ($headeractions !== '' ? html_writer::div($headeractions, 'commerce-plan-rules-header-actions') : ''),
    'commerce-plans-card-header commerce-plan-rules-header'
);
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-info-circle me-2', 'aria-hidden' => 'true']) . get_string('planupgradesintro', 'local_subscriptions'),
    'commerce-plan-subtle-note commerce-plan-rules-note'
);
if ($upgrades) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle mb-0';
    $table->head = [
        get_string('upgrade_fromplan', 'local_subscriptions'),
        get_string('upgrade_toplan', 'local_subscriptions'),
        get_string('upgrade_pricingmode', 'local_subscriptions'),
        get_string('status', 'local_subscriptions'),
        get_string('actions', 'local_subscriptions'),
    ];
    foreach ($upgrades as $upgrade) {
        $editurl = new moodle_url(subscription_config::plan_upgrades_page(), $pageparams + ['edit' => $upgrade->id]);
        $deleteurl = new moodle_url(subscription_config::plan_upgrades_page(), $pageparams + ['del' => $upgrade->id, 'sesskey' => sesskey()]);
        $actions = html_writer::link(
            $editurl,
            html_writer::tag('i', '', ['class' => 'fa fa-pencil', 'aria-hidden' => 'true']),
            ['class' => 'btn btn-sm btn-outline-primary me-1', 'title' => get_string('editupgrade', 'local_subscriptions')]
        );
        $actions .= html_writer::link(
            $deleteurl,
            html_writer::tag('i', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true']),
            [
                'class' => 'btn btn-sm btn-outline-danger',
                'title' => get_string('deleteupgrade', 'local_subscriptions'),
                'onclick' => 'return confirm(' . json_encode(get_string('confirmdeleteupgrade', 'local_subscriptions')) . ');',
            ]
        );
        $status = !empty($upgrade->isactive)
            ? html_writer::span(get_string('active', 'local_subscriptions'), 'badge rounded-pill text-bg-success')
            : html_writer::span(get_string('inactive', 'local_subscriptions'), 'badge rounded-pill bg-light text-dark border');
        $table->data[] = [
            html_writer::link(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $upgrade->fromplanid]), format_string($upgrade->fromplanname), ['class' => 'fw-semibold text-decoration-none']),
            html_writer::link(new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $upgrade->toplanid]), format_string($upgrade->toplanname), ['class' => 'fw-semibold text-decoration-none']),
            get_string('upgrade_pricing_' . $upgrade->pricingmode, 'local_subscriptions'),
            $status,
            $actions,
        ];
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive');
} else {
    echo html_writer::div(get_string('noupgrades', 'local_subscriptions'), 'card-body text-muted');
}
echo html_writer::end_div();

if ($edit || $add) {
    if ($edit) {
        $upgrade = $DB->get_record('subscription_plan_upgrade', ['id' => $edit], '*', MUST_EXIST);
        if ($planid && (int)$upgrade->fromplanid !== $planid && (int)$upgrade->toplanid !== $planid) {
            throw new moodle_exception('invalidparameter');
        }
        $mform->set_data($upgrade);
        $formtitle = get_string('editupgrade', 'local_subscriptions');
    } else {
        $defaults = (object)['pricingmode' => 'difference', 'isactive' => 1];
        if ($planid) {
            $defaults->fromplanid = $planid;
        }
        $mform->set_data($defaults);
        $formtitle = get_string('addupgrade', 'local_subscriptions');
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
