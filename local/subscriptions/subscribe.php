<?php
require('../../config.php');
require_once(__DIR__ . '/lib/user_subs_lib.php');
require_once(__DIR__ . '/lib/plans_lib.php');

// Pas besoin de require_login(); → page publique

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/subscribe.php'));
$PAGE->set_title(get_string('subscribe', 'local_subscriptions'));
$PAGE->set_heading(get_string('subscribe', 'local_subscriptions'));
$PAGE->requires->css('/local/subscriptions/styles.css');
$PAGE->requires->js_call_amd('local_subscriptions/plan_prices', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/guest_checkout', 'init');

$PAGE->set_pagelayout('standard');

// Rendu via renderer
echo $OUTPUT->header();

global $DB;

$plans = $DB->get_records('subscription_plan', ['is_active' => 1], 'name ASC');

$plans = sort_plans_by_duration($plans, true);

$renderer = $PAGE->get_renderer('local_subscriptions');
echo $renderer->render_available_plans($plans);

echo $OUTPUT->footer();
