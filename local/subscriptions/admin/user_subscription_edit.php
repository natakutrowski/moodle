<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/form/user_subscription_edit_form.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;
use local_subscriptions\form\user_subscription_edit_form;

require_login();
require_capability('moodle/site:config', context_system::instance());
subscription_config::guard_public_access();

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);

$context = context_system::instance();

$subscription = $DB->get_record('user_subscription', ['id' => $id], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $subscription->userid], '*', MUST_EXIST);
$plan = $DB->get_record('subscription_plan', ['id' => $subscription->planid], '*', MUST_EXIST);

$url = new moodle_url(subscription_config::user_subscription_edit_page(), ['id' => $id]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('edit_user_subscription', 'local_subscriptions'));
$PAGE->set_heading(get_string('edit_user_subscription', 'local_subscriptions'));

$form = new user_subscription_edit_form($url);

$defaultdata = [
    'id' => $subscription->id,
    'start_date' => (int)$subscription->start_date,
    'end_date' => !empty($subscription->end_date) ? (int)$subscription->end_date : time(),
    'no_end_date' => empty($subscription->end_date) || (int)$subscription->end_date > strtotime('2100-01-01'),
    'status' => $subscription->status,
];

$form->set_data($defaultdata);

if ($form->is_cancelled()) {
    redirect(new moodle_url(subscription_config::user_subscriptions_page()));
}

if ($data = $form->get_data()) {
    $startdate = (int)$data->start_date;
    $enddate = !empty($data->no_end_date) ? 0 : (int)$data->end_date;

    subscription_manager::update_subscription_from_admin(
        (int)$data->id,
        $startdate,
        $enddate,
        (string)$data->status
    );

    redirect(
        new moodle_url(subscription_config::user_subscriptions_page()),
        get_string('subscription_updated_successfully', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo html_writer::div(
    subscription_config::button_admin_dashboard(),
    'mb-3'
);

echo html_writer::div(
    html_writer::tag('h3', get_string('subscription_summary', 'local_subscriptions'), ['class' => 'h5 mb-3']) .
    html_writer::tag('p',
        html_writer::tag('strong', get_string('user', 'local_subscriptions') . ': ') .
        html_writer::link(new moodle_url('/user/profile.php', ['id' => $user->id]), fullname($user))
    ) .
    html_writer::tag('p',
        html_writer::tag('strong', get_string('email') . ': ') . s($user->email)
    ) .
    html_writer::tag('p',
        html_writer::tag('strong', get_string('plan', 'local_subscriptions') . ': ') . format_string($plan->name)
    ),
    'card card-body mb-4'
);

$form->display();

echo $OUTPUT->footer();