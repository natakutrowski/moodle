<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;
use local_subscriptions\constants\Status;

// Helpers (noms traduits plan/scope).
require_once(__DIR__ . '/../lib/plans_lib.php');
require_once(__DIR__ . '/../lib/scopes_lib.php');

// En-têtes JSON
header('Content-Type: application/json; charset=utf-8');

// Paramètres
$planid = required_param('planid', PARAM_INT);

global $DB;

$lang = current_language();
$context = context_system::instance();
$PAGE->set_context($context);

// Récupération du plan
$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);
$translation = $DB->get_record('subscription_plan_translation', [
    'planid' => $planid,
    'lang' => $lang
], '*', IGNORE_MISSING);

$planname = \local_subscriptions_plan_display_name($plan, $lang);

// Scope
$scope = subscription_manager::get_access_scope_from_planid($planid);
$scopename = \local_subscriptions_scope_display_name($scope, $lang);

// 3) Liste des cours (tri alpha).
$coursenames = [];
if (!empty($scope->course_ids)) {
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)$scope->course_ids))));
    if ($ids) {
        list($in, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_QM);
        $courses = $DB->get_records_select('course', "id $in", $params, 'fullname ASC', 'id, fullname');
        foreach ($courses as $c) {
            $coursenames[] = format_string($c->fullname);
        }
    }
}

// 4) Prix (planid -> [ "12.00 EUR", … ]).
$formattedprices = [];
foreach ($DB->get_records('subscription_plan_price', ['planid' => $planid], '', 'price, currency') as $p) {
    $formattedprices[] = sprintf('%s %s', number_format((float)$p->price, 2, '.'), $p->currency);
}

// 5) Description HTML (depuis translation + pluginfile rewrite).
$deschtml = local_subscriptions_plan_description_html($planid, $context, $lang, '-');

// 6) Réponse JSON.
try {
    echo json_encode([
        'name'        => $planname,
        'description' => $deschtml,
        'duration'    => subscription_config::get_plans()[$plan->duration_key] ?? $plan->duration_key,
        'accessscope' => $scopename,
        'courses'     => $coursenames,
        'prices'      => $formattedprices,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => Status::ERROR, 'message' => $e->getMessage()]);
}
exit;