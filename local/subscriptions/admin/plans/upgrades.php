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

$planid = optional_param('planid', 0, PARAM_INT);
$add = optional_param('add', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('del', 0, PARAM_INT);
$plan = $planid ? $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST) : null;

$pageparams = $planid ? ['planid' => $planid] : [];
$pageurl = new moodle_url(subscription_config::plan_upgrades_page(), $pageparams);
$pagetitle = get_string('planupgrades', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-plan-upgrades-page'
);

if ($plan) {
    $pagetitle = get_string(
        'commerce_plan_upgrades_for',
        'local_subscriptions',
        format_string($plan->name)
    );
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($pagetitle);
}

$PAGE->requires->css(new moodle_url('/local/subscriptions/select2.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/select2.min.js'), true);
$PAGE->requires->js_init_code("require(['jquery'], function($) { $(function() { if ($.fn.select2) { $('.select2').select2({width: '100%'}); } }); });");

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
    $upgrade = $DB->get_record('subscription_plan_upgrade', ['id' => $delete], '*', MUST_EXIST);
    $DB->delete_records('subscription_plan_upgrade', ['id' => $upgrade->id]);
    redirect($pageurl, get_string('upgradedeleted', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);

echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::admin_commerce_page())],
    ['label' => get_string('commerce_plans_title', 'local_subscriptions'), 'url' => new moodle_url(subscription_config::commerce_plans_page())],
    ['label' => $pagetitle, 'url' => null],
]);

$backurl = $plan
    ? new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid])
    : new moodle_url(subscription_config::commerce_plans_page());
echo CrmBackLinkRenderer::render(
    $backurl,
    $plan ? get_string('commerce_back_to_plan', 'local_subscriptions') : get_string('backtoplanlist', 'local_subscriptions')
);
echo CrmPageHeader::render($pagetitle, get_string('crm_plan_upgrades_description', 'local_subscriptions'), HelpContext::SUBSCRIPTIONS);
echo html_writer::div(get_string('planupgradesintro', 'local_subscriptions'), 'alert alert-info');

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

$table = new html_table();
$table->attributes['class'] = 'generaltable table table-bordered table-striped';
$table->head = [
    get_string('upgrade_fromplan', 'local_subscriptions'),
    get_string('upgrade_toplan', 'local_subscriptions'),
    get_string('upgrade_pricingmode', 'local_subscriptions'),
    get_string('status', 'local_subscriptions'),
    get_string('actions', 'local_subscriptions'),
];
foreach ($upgrades as $upgrade) {
    $urlparams = $pageparams + ['edit' => $upgrade->id];
    $editurl = new moodle_url(subscription_config::plan_upgrades_page(), $urlparams);
    $deleteurl = new moodle_url(subscription_config::plan_upgrades_page(), $pageparams + [
        'del' => $upgrade->id,
        'sesskey' => sesskey(),
    ]);
    $actions = html_writer::link($editurl, $OUTPUT->pix_icon('i/edit', get_string('editupgrade', 'local_subscriptions')), ['class' => 'me-2']);
    $actions .= html_writer::link($deleteurl, $OUTPUT->pix_icon('i/delete', get_string('deleteupgrade', 'local_subscriptions')), [
        'onclick' => 'return confirm(' . json_encode(get_string('confirmdeleteupgrade', 'local_subscriptions')) . ');',
    ]);
    $status = !empty($upgrade->isactive)
        ? html_writer::span(get_string('active', 'local_subscriptions'), 'badge bg-success')
        : html_writer::span(get_string('inactive', 'local_subscriptions'), 'badge bg-secondary');
    $table->data[] = [
        format_string($upgrade->fromplanname),
        format_string($upgrade->toplanname),
        get_string('upgrade_pricing_' . $upgrade->pricingmode, 'local_subscriptions'),
        $status,
        $actions,
    ];
}
if ($upgrades) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noupgrades', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
}

if ($edit) {
    $upgrade = $DB->get_record('subscription_plan_upgrade', ['id' => $edit], '*', MUST_EXIST);
    $mform->set_data($upgrade);
    echo $OUTPUT->heading(get_string('editupgrade', 'local_subscriptions'), 4);
    $mform->display();
} else if ($add) {
    $defaults = (object)['pricingmode' => 'difference', 'isactive' => 1];
    if ($planid) {
        $defaults->fromplanid = $planid;
    }
    $mform->set_data($defaults);
    echo $OUTPUT->heading(get_string('addupgrade', 'local_subscriptions'), 4);
    $mform->display();
} else {
    echo html_writer::link(
        new moodle_url(subscription_config::plan_upgrades_page(), $pageparams + ['add' => 1]),
        '➕ ' . get_string('addupgrade', 'local_subscriptions'),
        ['class' => 'btn btn-primary mb-3']
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
