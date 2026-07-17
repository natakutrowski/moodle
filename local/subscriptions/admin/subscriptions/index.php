<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/user_subs_renderer.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

global $DB, $OUTPUT, $PAGE;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$planid = optional_param('planid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);

$perpage = max(10, min(200, $perpage));

$urlparams = [
    'planid' => $planid,
    'perpage' => $perpage,
];

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::user_subscriptions_page(), $urlparams));
$PAGE->set_title(get_string('manage_user_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('manage_user_subscriptions', 'local_subscriptions'));
$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

$where = [];
$params = [];

if ($planid > 0) {
    $where[] = 'us.planid = :planid';
    $params['planid'] = $planid;
}

$wheresql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countsql = "
    SELECT COUNT(1)
      FROM {user_subscription} us
      JOIN {user} u ON u.id = us.userid
 LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
    $wheresql
";

$totalcount = $DB->count_records_sql($countsql, $params);

$sql = "
    SELECT
        us.*,
        u.firstname,
        u.lastname,
        u.email,
        u.firstnamephonetic,
        u.lastnamephonetic,
        u.middlename,
        u.alternatename,
        sp.name AS planname,
        sp.duration_key,
        sp.accessscopeid,
        sp.is_recurring
      FROM {user_subscription} us
      JOIN {user} u ON u.id = us.userid
 LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
    $wheresql
  ORDER BY us.start_date DESC, us.id DESC
";

$subscriptions = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$plans = [];
foreach ($DB->get_records('subscription_plan', null, 'name ASC', 'id, name') as $plan) {
    $translation = subscription_manager::get_translated_plan_name($plan->id, current_language());
    $plans[$plan->id] = $translation ?: format_string($plan->name);
}

$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

echo $OUTPUT->header();

echo AdminNavigation::back_button();

echo $renderer->render_user_subscriptions_admin_page(
    $subscriptions,
    $plans,
    $planid,
    $page,
    $perpage,
    $totalcount
);

echo $OUTPUT->footer();