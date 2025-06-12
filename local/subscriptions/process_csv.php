<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(subscription_config::process_csv_page()));
$PAGE->set_title(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();

$source = optional_param('sourcefile', '', PARAM_RAW);
if (!$source) {
    echo html_writer::div(get_string('missing_param', 'local_subscriptions'), 'subscription-message error');
    echo subscription_config::button_import_csv();
    echo $OUTPUT->footer();
    exit;
}

$data = base64_decode($source, true);
$validrows = unserialize($data); 

if (!is_array($validrows) || empty($validrows)) {
    echo html_writer::div(get_string('no_valid_rows', 'local_subscriptions'), 'subscription-message error');
    echo $OUTPUT->footer();
    exit;
}

$imported = 0;
$skipped = [];

foreach ($validrows as $assoc) {
    $email = trim($assoc['email']);
    $dateparts = explode('/', $assoc['start_date']);
    if (count($dateparts) === 3) {
        [$day, $month, $year] = $dateparts;
        $startdate = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
    } else {
        $startdate = false;
    }

    $plan = trim($assoc['plan']);
    $scope = trim($assoc['access_scope']);

    if (!$email || !$startdate || !$plan || !$scope) {
        $skipped[] = $assoc;
        continue;
    }

    $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
    if (!$user) {
        $skipped[] = $assoc;
        continue;
    }

    $enddate = subscription_manager::get_end_date_from_plan($plan, $startdate);
    subscription_manager::create_or_extend_subscription($user->id, $plan, 'csv', uniqid('csv_'), $startdate, $enddate, $scope, time());
    subscription_manager::enrol_user_to_courses($user->id, $scope);
    $imported++;
}


// Résumé
echo html_writer::div('✅ '.get_string('import_success_count', 'local_subscriptions', $imported), 'subscription-message success');

if (!empty($skipped)) {
    echo html_writer::div('⚠️ '.get_string('import_skipped', 'local_subscriptions'), 'subscription-message warning');
    echo html_writer::start_tag('ul');
    foreach ($skipped as $s) {
        echo html_writer::tag('li', implode(' | ', $s['data']) . ' (' . $s['reason'] . ')');
    }
    echo html_writer::end_tag('ul');
}

// Lien vers page de gestion
echo subscription_config::button_manage_subscription();

echo $OUTPUT->footer();
