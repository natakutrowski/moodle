<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(subscription_config::add_subscription_page()));
$PAGE->set_title(get_string('add_subscription', 'local_subscriptions'));
$PAGE->set_heading(get_string('add_subscription', 'local_subscriptions'));

$PAGE->requires->js('/local/subscriptions/js/select2.min.js');
$PAGE->requires->css('/local/subscriptions/select2.min.css');
$PAGE->requires->js('/local/subscriptions/js/init_select2.js');
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();

// Load data
$users = [];
$allusers = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);
foreach ($allusers as $user) {
    $users[$user->id] = fullname($user) . " ({$user->email})";
}

$plans = subscription_config::get_plans();
$scopes = subscription_config::get_scopes();

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = required_param('userid', PARAM_INT);

    if (optional_param('action', '', PARAM_TEXT) === 'enrol') {
        $plan = required_param('plan', PARAM_TEXT);
        $provider = 'manual';
        $subid = uniqid('manual_');
        $accessscope = optional_param('access_scope', null, PARAM_TEXT);
        $start = optional_param('start_date', time(), PARAM_INT);
        $end = subscription_manager::get_end_date_from_plan($plan, $start);

        $status = subscription_manager::create_or_extend_subscription($userid, $plan, $provider, $subid, $start, $end, $accessscope, time());
        
        $a = (object)[
            'user' => $users[$userid] ?? get_string('unknown_user', 'local_subscriptions'),
            'plan' => $plans[$plan] ?? $plan,
            'scope' => $scopes[$accessscope] ?? $accessscope
        ];

        if ($status === 'created') {
        	subscription_manager::enrol_user_to_courses($userid, $accessscope);
            echo html_writer::div(
                html_writer::span('✅', 'icon') . get_string('sub_created', 'local_subscriptions', $a),
                'subscription-message success'
            );
        } elseif ($status === 'exists') {
            echo html_writer::div(
                html_writer::span('ℹ️', 'icon') . get_string('sub_exists', 'local_subscriptions', $a),
                'subscription-message info'
            );
        }
    }

    if (optional_param('action', '', PARAM_TEXT) === 'enrol_test_only') {
        $plan = 'lifetime';
        $provider = 'manual';
        $subid = uniqid('manual_');
        $start = time();
        $end = subscription_manager::get_end_date_from_plan($plan, $start);

        subscription_manager::create_or_extend_subscription($userid, $plan, $provider, $subid, $start, $end, 'test', time());
        subscription_manager::enrol_user_to_courses($userid, 'test');

        $a = $users[$userid] ?? get_string('unknown_user', 'local_subscriptions');

        echo html_writer::div(
            html_writer::span('📘', 'icon') . get_string('sub_test_done', 'local_subscriptions', $a),
            'subscription-message success'
        );
    }
}

// Form
echo html_writer::start_div('subscription-card');

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mform']);

// User
echo html_writer::div(
    html_writer::label(get_string('select_user', 'local_subscriptions'), 'userid') .
    html_writer::select($users, 'userid', '', ['' => '—'], ['class' => 'form-control select2']),
    'form-group'
);

// Plan
echo html_writer::div(
    html_writer::label(get_string('plan', 'local_subscriptions'), 'plan') .
    html_writer::select($plans, 'plan', '', ['' => '—'], ['class' => 'form-control select2']),
    'form-group'
);

// Scope
echo html_writer::div(
    html_writer::label(get_string('access_scope', 'local_subscriptions'), 'access_scope') .
    html_writer::select($scopes, 'access_scope', '', ['' => '—'], ['class' => 'form-control select2']),
    'form-group'
);

// Start date
echo html_writer::div(
    html_writer::label(get_string('start_date', 'local_subscriptions'), 'start_date') .
    html_writer::empty_tag('input', [
        'type' => 'date',
        'name' => 'start_date',
        'class' => 'form-control',
        'value' => date('Y-m-d')
    ]),
    'form-group'
);

// Buttons
echo html_writer::div(
    html_writer::tag('button', '📘 '.get_string('submit_sub', 'local_subscriptions'), [
        'type' => 'submit',
        'name' => 'action',
        'value' => 'enrol',
        'class' => 'btn btn-primary me-2'
    ]) .
    html_writer::tag('button', '🧪 '.get_string('submit_sub_test', 'local_subscriptions'), [
        'type' => 'submit',
        'name' => 'action',
        'value' => 'enrol_test_only',
        'class' => 'btn btn-outline-primary me-4'
    ]) .    
    subscription_config::button_manage_subscription() .
    subscription_config::button_import_csv(),
    'form-group d-flex flex-wrap align-items-center',
    ['style' => 'margin-top: 30px; gap: 10px;']
);

echo html_writer::end_tag('form');

echo html_writer::end_div(); // Fin du card

echo $OUTPUT->footer();
