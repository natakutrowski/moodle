<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminNavigation;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\automation\AutomationHistoryRepository;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::automation_history_admin_page()));
$PAGE->set_title(get_string('crm_automation_history', 'local_subscriptions'));
$PAGE->set_heading(get_string('crm_automation_history', 'local_subscriptions'));
$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

$history = (new AutomationHistoryRepository())->get_recent(100);

echo $OUTPUT->header();
echo AdminNavigation::back_button();

echo html_writer::link(
    new moodle_url(subscription_config::automation_rules_admin_page()),
    '⚙️ ' . get_string('crm_automations', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary mb-3']
);

if (empty($history)) {
    echo html_writer::div(
        get_string('crm_automation_no_history', 'local_subscriptions'),
        'alert alert-light'
    );
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('date'),
    get_string('status'),
    get_string('crm_automation_rule', 'local_subscriptions'),
    get_string('crm_automation_trigger', 'local_subscriptions'),
    get_string('user'),
    get_string('message'),
];

foreach ($history as $entry) {
    $user = '-';

    if (!empty($entry->userid)) {
        $user = html_writer::link(
            new moodle_url(subscription_config::admin_user_view_page(), ['id' => $entry->userid]),
            '#' . (int)$entry->userid
        );
    }

    $table->data[] = [
        AdminFormatter::datetime((int)$entry->timecreated),
        AdminFormatter::automation_status((string)$entry->status),
        s($entry->rulekey),
        s($entry->triggerkey),
        $user,
        s((string)($entry->message ?? '')),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();