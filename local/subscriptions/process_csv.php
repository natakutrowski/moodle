<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/process_csv.php'));
$PAGE->set_title(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import_subscriptions', 'local_subscriptions'));

$source = optional_param('sourcefile', '', PARAM_RAW);
if (!$source) {
    echo html_writer::div(get_string('missing_param', 'local_subscriptions'), 'subscription-message error');
    echo $OUTPUT->footer();
    exit;
}

$source = optional_param('sourcefile', '', PARAM_RAW);
$data = base64_decode($source, true);
if ($data === false) {
    throw new moodle_exception('invalidbase64', 'error');
}

$handle = fopen('php://temp', 'r+');
fwrite($handle, $data);
rewind($handle);

$header = fgetcsv($handle, 0, ',');
$imported = 0;
$skipped = [];
while (($row = fgetcsv($handle, 0, ',')) !== false) {
    if (count($row) < 4) continue;

    $assoc = array_combine($header, $row);
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
        $skipped[] = $row;
        continue;
    }

    $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
    if (!$user) {
        $skipped[] = $row;
        continue;
    }

    $enddate = subscription_manager::get_end_date_from_plan($plan, $startdate);
    subscription_manager::create_or_extend_subscription($user->id, $plan, 'csv', uniqid('csv_'), $startdate, $enddate, $scope);
    subscription_manager::enrol_user_to_courses($user->id, $scope);
    $imported++;
}

echo html_writer::div(get_string('import_success_count', 'local_subscriptions', $imported), 'subscription-message success');

if (!empty($skipped)) {
    echo html_writer::div(get_string('import_skipped', 'local_subscriptions'), 'subscription-message warning');
    echo html_writer::start_tag('ul');
    foreach ($skipped as $s) {
        echo html_writer::tag('li', implode(' | ', $s));
    }
    echo html_writer::end_tag('ul');
}

if ($imported > 0) {
    $manageurl = new moodle_url('/local/subscriptions/manage.php');
    $buttonhtml = html_writer::tag('a',
        get_string('gotomanagepage', 'local_subscriptions'),
        [
            'href' => $manageurl->out(),
            'class' => 'btn btn-primary',
            'style' => 'margin-top: 20px; display: inline-block;'
        ]
    );
    echo html_writer::div($buttonhtml, 'centered');
}


echo $OUTPUT->footer();
