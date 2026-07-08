<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminNavigation;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\automation\AutomationRuleService;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::automation_rules_admin_page()));
$PAGE->set_title(get_string('crm_automations', 'local_subscriptions'));
$PAGE->set_heading(get_string('crm_automations', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));

$service = new AutomationRuleService();
$rules = $service->get_all();

echo $OUTPUT->header();
echo AdminNavigation::back_button();

echo html_writer::div(
    html_writer::link(
        new moodle_url(subscription_config::automation_history_admin_page()),
        '🕘 ' . get_string('crm_automation_history', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary mb-3']
    )
);

echo html_writer::tag('h3', get_string('crm_automations', 'local_subscriptions'));
echo html_writer::div(
    get_string(
        'crm_automation_rules_count',
        'local_subscriptions',
        count($rules)
    ),
    'alert alert-info'
);

if (empty($rules)) {
    echo html_writer::div(
        get_string('crm_automation_no_rules', 'local_subscriptions'),
        'alert alert-light'
    );
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('status'),
    get_string('name'),
    get_string('crm_automation_trigger', 'local_subscriptions'),
    get_string('priority', 'local_subscriptions'),
    get_string('actions'),
];

foreach ($rules as $rule) {
    $enabled = $rule->enabled;

    $toggleurl = new moodle_url(subscription_config::automation_toggle_admin_page(), [
        'id' => $rule->id,
        'sesskey' => sesskey(),
        'action' => $enabled ? 'disable' : 'enable',
    ]);

    $table->data[] = [
        $enabled ? '✅ ' . get_string('enabled', 'local_subscriptions') : '⛔ ' . get_string('disabled', 'local_subscriptions'),
        s($rule->name) . html_writer::div(s($rule->key), 'text-muted small'),
        s($rule->trigger->key),
        (int)$rule->priority,
        html_writer::link(
            $toggleurl,
            $enabled ? get_string('disable') : get_string('enable'),
            ['class' => $enabled ? 'btn btn-sm btn-outline-danger' : 'btn btn-sm btn-outline-success']
        ),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();