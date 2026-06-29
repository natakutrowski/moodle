<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/user_subs_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/user_subs_renderer.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

function local_subscriptions_generate_unique_username_from_email(string $email): string {
    global $DB;

    $base = core_text::strtolower(trim($email));
    $base = clean_param($base, PARAM_USERNAME);

    if ($base === '') {
        $base = 'user';
    }

    $username = $base;
    $i = 1;

    while ($DB->record_exists('user', ['username' => $username])) {
        $username = $base . '.' . $i;
        $i++;
    }

    return $username;
}

global $DB, $PAGE, $OUTPUT, $CFG;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::add_manual_subscription_page()));
$PAGE->set_title(get_string('add_subscription', 'local_subscriptions'));
$PAGE->set_heading(get_string('add_subscription', 'local_subscriptions'));

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css('/local/subscriptions/styles.css');

$PAGE->requires->css(new moodle_url('/local/subscriptions/thirdparty/flatpickr/flatpickr.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/thirdparty/flatpickr/flatpickr.min.js'), true);
$PAGE->requires->js(new moodle_url('/local/subscriptions/thirdparty/flatpickr/l10n/fr.js'), true);

$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

$plans = [];
foreach ($DB->get_records('subscription_plan', null, 'name ASC') as $plan) {
    $translation = subscription_manager::get_translated_plan_name($plan->id, current_language());
    $label = $translation ?: format_string($plan->name);

    if (empty($plan->is_active)) {
        $label .= ' ' . get_string('label_inactive', 'local_subscriptions');
    }

    $plans[$plan->id] = $label;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $planid = required_param('plan', PARAM_INT);
    $price = required_param('price', PARAM_FLOAT);
    $currency = required_param('currency', PARAM_ALPHA);
    $currency = strtoupper($currency);
    $pricecurrency = number_format($price, 2, '.', '') . '|' . $currency;

    $startraw = optional_param('start_date', '', PARAM_RAW_TRIMMED);

    if ($startraw !== '' && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $startraw, $m)) {
        $startraw = $m[3] . '-' . $m[2] . '-' . $m[1];
    }  

    $userid = optional_param('userid', 0, PARAM_INT);
    $usermode = required_param('user_mode', PARAM_ALPHA);

    if ($usermode === 'new') {
        $firstname = required_param('firstname', PARAM_TEXT);
        $lastname = required_param('lastname', PARAM_TEXT);
        $email = required_param('email', PARAM_EMAIL);
        $country = optional_param('country', '', PARAM_ALPHA);
        $country = strtoupper($country);

        $existing = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MISSING);

        if ($existing) {
            $userid = (int)$existing->id;
        } else {
            $password = generate_password(12);

            $newuser = (object)[
                'auth' => 'manual',
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id,
                'username' => local_subscriptions_generate_unique_username_from_email($email),
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'country' => $country,
                'password' => hash_internal_user_password($password),
                'timecreated' => time(),
                'timemodified' => time(),
            ];

            $userid = user_create_user($newuser, false, false);
        }
    }

    if ($userid <= 0) {
        throw new moodle_exception('missing_user_for_manual_subscription', 'local_subscriptions');
    }

    $status = local_subscriptions_enrol_user_manual($userid, $planid, $pricecurrency, $startraw, true);

    $user = core_user::get_user($userid);
    $planlabel = $plans[$planid] ?? $planid;

    $a = (object)[
        'user' => fullname($user) . ' (' . $user->email . ')',
        'plan' => $planlabel,
    ];

    if ($status === 'created') {
        \core\notification::success(get_string('sub_created', 'local_subscriptions', $a));
    } else {
        \core\notification::info(get_string('sub_exists', 'local_subscriptions', $a));
    }

    redirect(new moodle_url(subscription_config::user_subscriptions_page(), ['planid' => $planid]));
}

echo $OUTPUT->header();

echo AdminNavigation::back_button();

echo $renderer->render_manual_subscription_form_v2($plans);

echo $OUTPUT->footer();