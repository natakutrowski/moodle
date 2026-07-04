<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_upgrade_form.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

global $DB, $OUTPUT, $PAGE;

$add = optional_param('add', 0, PARAM_BOOL);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('del', 0, PARAM_INT);

$pageurl = new moodle_url(subscription_config::plan_upgrades_page());

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('planupgrades', 'local_subscriptions'));
$PAGE->set_heading(get_string('planupgrades', 'local_subscriptions'));

$PAGE->requires->css(new moodle_url('/local/subscriptions/select2.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/select2.min.js'), true);
$PAGE->requires->js_init_code("
    require(['jquery'], function($) {
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2').select2({ width: '100%' });
            }
        });
    });
");

$mform = new plan_upgrade_form($pageurl);

if ($mform->is_cancelled()) {
    redirect($pageurl);
}

if ($data = $mform->get_data()) {
    $now = time();

    $record = (object)[
        'fromplanid' => (int)$data->fromplanid,
        'toplanid' => (int)$data->toplanid,
        'pricingmode' => trim($data->pricingmode),
        'isactive' => (int)$data->isactive,
        'lastupdate' => $now,
    ];

    if (!empty($data->id)) {
        $record->id = (int)$data->id;

        $DB->update_record('subscription_plan_upgrade', $record);

        redirect(
            $pageurl,
            get_string('upgradeupdated', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    $record->timecreated = $now;

    $DB->insert_record('subscription_plan_upgrade', $record);

    redirect(
        $pageurl,
        get_string('upgradecreated', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($delete && confirm_sesskey()) {
    $DB->delete_records('subscription_plan_upgrade', ['id' => $delete]);

    redirect(
        $pageurl,
        get_string('upgradedeleted', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo AdminNavigation::back_button();

echo $OUTPUT->heading(get_string('planupgrades', 'local_subscriptions'), 3);

echo html_writer::div(
    get_string('planupgradesintro', 'local_subscriptions'),
    'alert alert-info'
);

$upgrades = $DB->get_records_sql("
    SELECT u.*,
           fp.name AS fromplanname,
           tp.name AS toplanname
      FROM {subscription_plan_upgrade} u
      JOIN {subscription_plan} fp ON fp.id = u.fromplanid
      JOIN {subscription_plan} tp ON tp.id = u.toplanid
  ORDER BY fp.name ASC, tp.name ASC
");

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
    $editurl = new moodle_url(subscription_config::plan_upgrades_page(), [
        'edit' => $upgrade->id,
    ]);

    $deleteurl = new moodle_url(subscription_config::plan_upgrades_page(), [
        'del' => $upgrade->id,
        'sesskey' => sesskey(),
    ]);

    $actions = html_writer::link(
        $editurl,
        $OUTPUT->pix_icon('i/edit', get_string('editupgrade', 'local_subscriptions')),
        ['class' => 'me-2']
    );

    $actions .= html_writer::link(
        $deleteurl,
        $OUTPUT->pix_icon('i/delete', get_string('deleteupgrade', 'local_subscriptions')),
        [
            'onclick' => "return confirm(" . json_encode(get_string('confirmdeleteupgrade', 'local_subscriptions')) . ");",
        ]
    );

    $status = !empty($upgrade->isactive)
        ? html_writer::span(get_string('active', 'local_subscriptions'), 'badge bg-success')
        : html_writer::span(get_string('inactive', 'local_subscriptions'), 'badge bg-secondary');

    $pricinglabel = get_string('upgrade_pricing_' . $upgrade->pricingmode, 'local_subscriptions');

    $table->data[] = [
        s($upgrade->fromplanname),
        s($upgrade->toplanname),
        $pricinglabel,
        $status,
        $actions,
    ];
}

if ($upgrades) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(
        get_string('noupgrades', 'local_subscriptions'),
        \core\output\notification::NOTIFY_INFO
    );
}

$returnurl = new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']);

echo html_writer::div(
    html_writer::link(
        $returnurl,
        '← ' . get_string('backtoplanlist', 'local_subscriptions'),
        ['class' => 'btn btn-link']
    ),
    'mb-3'
);

if ($edit) {
    $upgrade = $DB->get_record('subscription_plan_upgrade', ['id' => $edit], '*', MUST_EXIST);
    $mform->set_data($upgrade);

    echo $OUTPUT->heading(get_string('editupgrade', 'local_subscriptions'), 4);
    $mform->display();

} else if ($add) {
    $mform->set_data((object)[
        'pricingmode' => 'difference',
        'isactive' => 1,
    ]);

    echo $OUTPUT->heading(get_string('addupgrade', 'local_subscriptions'), 4);
    $mform->display();

} else {
    echo html_writer::link(
        new moodle_url(subscription_config::plan_upgrades_page(), ['add' => 1]),
        '➕ ' . get_string('addupgrade', 'local_subscriptions'),
        [
            'class' => 'btn btn-primary',
            'style' => 'margin-bottom: 1em; display: inline-block;',
        ]
    );
}

echo $OUTPUT->footer();