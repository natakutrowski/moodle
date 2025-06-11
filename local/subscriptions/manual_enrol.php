<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/manual_enrol.php'));
$PAGE->set_title(get_string('manual_enrol', 'local_subscriptions'));
$PAGE->set_heading(get_string('manual_enrol', 'local_subscriptions'));

$PAGE->requires->js('/local/subscriptions/select2.min.js');
$PAGE->requires->css('/local/subscriptions/select2.min.css');
$PAGE->requires->js('/local/subscriptions/init_select2.js');
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manual_enrol', 'local_subscriptions'));

// Load data
$users = [];
$allusers = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);
foreach ($allusers as $user) {
    $users[$user->id] = fullname($user) . " ({$user->email})";
}

$plans = [
    '1month' => get_string('plan_1month', 'local_subscriptions'),
    '3months' => get_string('plan_3months', 'local_subscriptions'),
    '6months' => get_string('plan_6months', 'local_subscriptions'),
    '1year' => get_string('plan_1year', 'local_subscriptions'),
    '3years' => get_string('plan_3years', 'local_subscriptions'),
    'unlimited' => get_string('plan_unlimited', 'local_subscriptions'),
];

$scopes = [
    'full' => get_string('access_full', 'local_subscriptions'),
    'a0_only' => get_string('access_a0', 'local_subscriptions'),
    'a1_only' => get_string('access_a1', 'local_subscriptions'),
    'a2_only' => get_string('access_a2', 'local_subscriptions'),
];

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

        $status = subscription_manager::create_or_extend_subscription($userid, $plan, $provider, $subid, $start, $end, $accessscope);
        subscription_manager::enrol_user_to_courses($userid, $accessscope);

        $a = (object)[
            'user' => $users[$userid] ?? get_string('unknown_user', 'local_subscriptions'),
            'plan' => $plans[$plan] ?? $plan,
            'scope' => $scopes[$accessscope] ?? $accessscope
        ];

        if ($status === 'created') {
            echo html_writer::div(
                html_writer::span('✅', 'icon') . get_string('enrol_success', 'local_subscriptions', $a),
                'subscription-message success'
            );
        } else {
            echo html_writer::div(
                html_writer::span('🔁', 'icon') . get_string('enrol_extended', 'local_subscriptions', $a),
                'subscription-message info'
            );
        }
    }

    if (optional_param('action', '', PARAM_TEXT) === 'enrol_test_only') {
        $plan = required_param('plan', PARAM_TEXT);
        $provider = 'manual';
        $subid = uniqid('manual_');
        $start = optional_param('start_date', time(), PARAM_INT);
        $end = subscription_manager::get_end_date_from_plan($plan, $start);

        subscription_manager::create_or_extend_subscription($userid, $plan, $provider, $subid, $start, $end, 'test');
        subscription_manager::enrol_user_to_courses($userid, 'test');

        $a = $users[$userid] ?? get_string('unknown_user', 'local_subscriptions');

        echo html_writer::div(
            html_writer::span('📘', 'icon') . get_string('enrol_test_done', 'local_subscriptions', $a),
            'subscription-message success'
        );
    }

    if (optional_param('action', '', PARAM_TEXT) === 'unenrol_all') {
        subscription_manager::unenrol_user_from_all_courses($userid);
        $a = $users[$userid] ?? get_string('unknown_user', 'local_subscriptions');

        echo html_writer::div(
            html_writer::span('🗑️', 'icon') . get_string('unenrol_success', 'local_subscriptions', $a),
            'subscription-message success'
        );
    }
}

// Form
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mform']);
echo html_writer::start_tag('fieldset', ['style' => 'max-width: 650px; padding: 20px; background: #f9f9f9; border-radius: 8px;']);
echo html_writer::tag('legend', get_string('manual_enrol', 'local_subscriptions'));

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
    html_writer::tag('button', get_string('submit_enrol', 'local_subscriptions'), ['type' => 'submit', 'name' => 'action', 'value' => 'enrol', 'class' => 'btn btn-primary']) . ' ' .
    html_writer::tag('button', get_string('submit_enrol_test', 'local_subscriptions'), ['type' => 'submit', 'name' => 'action', 'value' => 'enrol_test_only', 'class' => 'btn btn-secondary']) . ' ' .
    html_writer::tag('button', get_string('submit_unenrol_all', 'local_subscriptions'), ['type' => 'submit', 'name' => 'action', 'value' => 'unenrol_all', 'class' => 'btn btn-danger']),
    'form-group', ['style' => 'margin-top: 25px;']
);

echo html_writer::end_tag('fieldset');
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
