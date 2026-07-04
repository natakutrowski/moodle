<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_entitlement_form.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

global $DB, $OUTPUT, $PAGE;

$planid = optional_param('planid', 0, PARAM_INT);
$add = optional_param('add', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

if ($planid <= 0 || !$plan = $DB->get_record('subscription_plan', ['id' => $planid])) {
    redirect(
        new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
        get_string('invalidplanid', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$pageurl = new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid]);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('planentitlements', 'local_subscriptions'));
$PAGE->set_heading(get_string('planentitlementsfor', 'local_subscriptions', $plan->name));

$PAGE->requires->css(new moodle_url('/local/subscriptions/select2.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/select2.min.js'), true);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));

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

        redirect($pageurl,
            get_string('entitlementupdated', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    $record->timecreated = $now;
    $DB->insert_record('subscription_plan_entitlement', $record);

    redirect($pageurl,
        get_string('entitlementcreated', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($delete && confirm_sesskey()) {
    $entitlement = $DB->get_record('subscription_plan_entitlement', [
        'id' => $delete,
        'planid' => $planid,
    ], '*', MUST_EXIST);

    $DB->delete_records('subscription_plan_entitlement', ['id' => $entitlement->id]);

    redirect($pageurl,
        get_string('entitlementdeleted', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo AdminNavigation::back_button();

echo $OUTPUT->heading(get_string('planentitlementsfor', 'local_subscriptions', $plan->name), 3);

$entitlements = $DB->get_records_sql("
    SELECT e.*, c.fullname AS coursename
      FROM {subscription_plan_entitlement} e
      JOIN {course} c ON c.id = e.courseid
     WHERE e.planid = :planid
  ORDER BY e.priority DESC, c.fullname ASC, e.accesslevel ASC
", ['planid' => $planid]);

$table = new html_table();
$table->attributes['class'] = 'generaltable table table-bordered table-striped';
$table->head = [
    get_string('entitlement_course', 'local_subscriptions'),
    get_string('entitlement_accesslevel', 'local_subscriptions'),
    get_string('entitlement_role', 'local_subscriptions'),
    get_string('entitlement_groupname', 'local_subscriptions'),
    get_string('entitlement_priority', 'local_subscriptions'),
    get_string('actions', 'local_subscriptions'),
];

foreach ($entitlements as $e) {
    $editurl = new moodle_url(subscription_config::plan_entitlements_page(), [
        'planid' => $planid,
        'edit' => $e->id,
    ]);

    $deleteurl = new moodle_url(subscription_config::plan_entitlements_page(), [
        'planid' => $planid,
        'delete' => $e->id,
        'sesskey' => sesskey(),
    ]);

    $actions = \html_writer::link($editurl,
        $OUTPUT->pix_icon('i/edit', get_string('editentitlement', 'local_subscriptions')),
        ['class' => 'me-2']
    );

    $actions .= \html_writer::link($deleteurl,
        $OUTPUT->pix_icon('i/delete', get_string('deleteentitlement', 'local_subscriptions')),
        [
            'onclick' => "return confirm(" . json_encode(get_string('confirmdeleteentitlement', 'local_subscriptions')) . ");",
        ]
    );

    $accesslabel = get_string('accesslevel_' . $e->accesslevel, 'local_subscriptions');

    $table->data[] = [
        format_string($e->coursename),
        $accesslabel,
        s($e->roleshortname),
        $e->groupname ? s($e->groupname) : \html_writer::span(get_string('none', 'local_subscriptions'), 'text-muted'),
        (int)$e->priority,
        $actions,
    ];
}

if ($entitlements) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noentitlements', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
}

$returnurl = new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']);
echo \html_writer::div(
    \html_writer::link($returnurl, '← ' . get_string('backtoplanlist', 'local_subscriptions'), ['class' => 'btn btn-link']),
    'mb-3'
);

if ($edit) {
    $entitlement = $DB->get_record('subscription_plan_entitlement', [
        'id' => $edit,
        'planid' => $planid,
    ], '*', MUST_EXIST);

    $mform->set_data($entitlement);

    echo $OUTPUT->heading(get_string('editentitlement', 'local_subscriptions'), 4);
    $mform->display();

} else if ($add) {
    $mform->set_data((object)['planid' => $planid, 'priority' => 100]);

    echo $OUTPUT->heading(get_string('addentitlement', 'local_subscriptions'), 4);
    $mform->display();

} else {
    echo \html_writer::link(
        new moodle_url(subscription_config::plan_entitlements_page(), ['planid' => $planid, 'add' => 1]),
        '➕ ' . get_string('addentitlement', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    );
}

echo $OUTPUT->footer();