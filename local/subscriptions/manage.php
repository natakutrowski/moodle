<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/manage.php'));
$PAGE->set_title(get_string('active_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('active_subscriptions', 'local_subscriptions'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('active_subscriptions', 'local_subscriptions'));

// Récupérer les abonnements actifs
global $DB;
$subscriptions = $DB->get_records('user_subscription', ['status' => 'active'], 'start_date DESC');

if (empty($subscriptions)) {
    echo $OUTPUT->notification(get_string('no_active_subscriptions', 'local_subscriptions'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = html_writer::start_tag('table', ['class' => 'generaltable']);
$table .= html_writer::start_tag('thead');
$table .= html_writer::start_tag('tr');
$table .= html_writer::tag('th', get_string('user', 'local_subscriptions'));
$table .= html_writer::tag('th', get_string('plan', 'local_subscriptions'));
$table .= html_writer::tag('th', get_string('start', 'local_subscriptions'));
$table .= html_writer::tag('th', get_string('end', 'local_subscriptions'));
$table .= html_writer::tag('th', get_string('status', 'local_subscriptions'));
$table .= html_writer::tag('th', get_string('courses', 'local_subscriptions'));
$table .= html_writer::end_tag('tr');
$table .= html_writer::end_tag('thead');

$table .= html_writer::start_tag('tbody');

foreach ($subscriptions as $sub) {
    $user = $DB->get_record('user', ['id' => $sub->userid]);
    $username = fullname($user) . ' (' . $user->email . ')';

    $start = userdate($sub->start_date);
    $end = userdate($sub->end_date);

    // Récupérer les cours de l'utilisateur
    $courses = subscription_manager::get_all_manual_courses_for_user($user->id);
    $course_links = array_map(function($course) use ($user) {
		$courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
		$unenrolurl = new moodle_url('/local/subscriptions/unenrol.php', [
			'userid' => $user->id,
			'courseid' => $course->id,
			'returnurl' => '/local/subscriptions/manage.php'
		]);
	
		$link = html_writer::link($courseurl, $course->fullname);
		$unenrollink = html_writer::link($unenrolurl, ' ✖', ['onclick' => "return confirm('Unenrol user from this course?');", 'style' => 'color: red; margin-left: 5px; text-decoration: none;']);
		return $link . $unenrollink;
	}, $courses);

    $course_list = implode('<br>', $course_links);

    $table .= html_writer::start_tag('tr');
    $table .= html_writer::tag('td', $username);
    $table .= html_writer::tag('td', $sub->plan);
    $table .= html_writer::tag('td', $start);
    $table .= html_writer::tag('td', $end);
    $table .= html_writer::tag('td', $sub->status);
    $table .= html_writer::tag('td', $course_list);
    $table .= html_writer::end_tag('tr');
}

$table .= html_writer::end_tag('tbody');
$table .= html_writer::end_tag('table');

echo $table;
echo $OUTPUT->footer();
