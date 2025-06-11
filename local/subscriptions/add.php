<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/add.php'));
$PAGE->set_title(get_string('add_subscription', 'local_subscriptions'));
$PAGE->set_heading(get_string('add_subscription', 'local_subscriptions'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('subscription_form_heading', 'local_subscriptions'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = required_param('userid', PARAM_INT);
    $plan = required_param('plan', PARAM_TEXT);
    $provider = 'manual';
    $subid = 'manual-' . time();
    $start = time();
    $end = \local_subscriptions\subscription_manager::get_end_date_from_plan($plan);
    $accessscope = optional_param('access_scope', null, PARAM_TEXT);

    if (! $DB->record_exists('user', ['id' => $userid])) {
        echo $OUTPUT->notification(get_string('invalid_user', 'local_subscriptions'), 'notifyproblem');
    } else {
        \local_subscriptions\subscription_manager::create_subscription($userid, $plan, $provider, $subid, $start, $end, $accessscope);

	// Inscription automatique selon access_scope
	if (!empty($accessscope)) {
		\local_subscriptions\subscription_manager::enrol_user_to_courses($userid, $accessscope);
	}

        echo $OUTPUT->notification(get_string('subscription_added', 'local_subscriptions'), 'notifysuccess');
    }
}

// Créer un sélecteur d’utilisateur
$users = $DB->get_records_sql('SELECT id, firstname, lastname, email FROM {user} ORDER BY lastname ASC');

echo '<form method="post">';
echo '<label>' . get_string('choose_user', 'local_subscriptions') . ' :</label><br>';
echo '<select name="userid" required>';
foreach ($users as $user) {
    echo '<option value="' . $user->id . '">' . fullname($user) . ' (' . $user->email . ')</option>';
}
echo '</select><br><br>';

echo '<label>' . get_string('plan', 'local_subscriptions') . ' :</label><br>';
echo '<select name="plan" required>
        <option value="1month">' . get_string('plan_1month', 'local_subscriptions') . '</option>
        <option value="3months">' . get_string('plan_3months', 'local_subscriptions') . '</option>
        <option value="6months">' . get_string('plan_6months', 'local_subscriptions') . '</option>
        <option value="1year">' . get_string('plan_1year', 'local_subscriptions') . '</option>
        <option value="3years">' . get_string('plan_3years', 'local_subscriptions') . '</option>
        <option value="unlimited">' . get_string('plan_unlimited', 'local_subscriptions') . '</option>
      </select><br><br>';
echo '<label>Access scope (optional) :</label><br>';
echo '<input type="text" name="access_scope" size="50"><br><br>';
echo '<button type="submit">' . get_string('submit', 'local_subscriptions') . '</button>';
echo '</form>';

echo $OUTPUT->footer();
