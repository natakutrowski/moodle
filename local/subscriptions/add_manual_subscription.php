<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib/user_subs_lib.php');
require_once(__DIR__ . '/renderer/user_subs_renderer.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

subscription_config::guard_public_access();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(subscription_config::add_manual_subscription_page()));
$PAGE->set_title(get_string('add_subscription', 'local_subscriptions'));
$PAGE->set_heading(get_string('add_subscription', 'local_subscriptions'));

$PAGE->requires->js('/local/subscriptions/js/select2.min.js');
$PAGE->requires->css('/local/subscriptions/select2.min.css');
$PAGE->requires->js('/local/subscriptions/js/init_select2.js');
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();
$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

// Load data
$users = [];
$allusers = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);
foreach ($allusers as $user) {
    $users[$user->id] = fullname($user) . " ({$user->email})";
}

$plans = [];
foreach ($DB->get_records('subscription_plan', null, 'name ASC') as $plan) {
    $translation = subscription_manager::get_translated_plan_name($plan->id, current_language()); // à adapter
    $label = $translation ?: '<i>' . format_string($plan->name) . '</i>';
    if (empty($plan->is_active)) {
        $label .= ' ' . get_string('label_inactive', 'local_subscriptions'); // (inactif)
    }
    $plans[$plan->id] = $label;
}

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $userid = required_param('userid', PARAM_INT);

    if (optional_param('action', '', PARAM_TEXT) === 'enrol') {

        $planid = required_param('plan', PARAM_INT);
        $pricecurrency = required_param('price_currency', PARAM_RAW_TRIMMED); // Ex: "100|EUR"
        $start_raw = optional_param('start_date', '', PARAM_RAW_TRIMMED);

        $status = local_subscriptions_enrol_user_manual($userid, $planid, $pricecurrency, $start_raw, true);
       
        $a = (object)[
            'user' => $users[$userid] ?? get_string('unknown_user', 'local_subscriptions'),
            'plan' => $plans[$planid] ?? $planid
        ];

        if ($status === 'created') {
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
        try {
            $status = local_subscriptions_enrol_user_test($userid);

            $a = $users[$userid] ?? get_string('unknown_user', 'local_subscriptions');
            echo html_writer::div(
                html_writer::span('📘', 'icon') . get_string('sub_test_done', 'local_subscriptions', $a),
                'subscription-message success'
            );
        } catch (moodle_exception $e) {
            echo html_writer::div(
                html_writer::span('⚠️', 'icon') . $e->getMessage(),
                'subscription-message error'
            );
        }
    }

}
echo $renderer->render_manual_subscription_form($users, $plans);
echo $OUTPUT->footer();