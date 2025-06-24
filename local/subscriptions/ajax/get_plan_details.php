<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

// En-têtes JSON
header('Content-Type: application/json');

// Paramètres
$planid = required_param('planid', PARAM_INT);

// Récupération du plan
global $DB, $USER;

$planrecord = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);
$lang = current_language();

// Traduction du nom et de la description
$translatedname = $DB->get_field('subscription_plan_translation', 'name', ['plan_id' => $planid, 'lang' => $lang]);
$translateddesc = $DB->get_field('subscription_plan_translation', 'description', ['plan_id' => $planid, 'lang' => $lang]);

// Scope
$scoperecord = subscription_manager::get_access_scope_from_planid($planid);
$coursenames = [];

$courseids = [];

if (!empty($scoperecord->course_ids)) {
    $ids = explode(',', $scoperecord->course_ids); // si stocké "12,45,89"
    $ids = array_map('intval', $ids); // sécurise les entiers
    $ids = array_filter($ids); // supprime les éventuels 0 ou vides

    if (!empty($ids)) {
        list($in_sql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_QM);
        $courserecords = $DB->get_records_select('course', "id $in_sql", $params, 'fullname ASC', 'id, fullname');

        foreach ($courserecords as $c) {
            $coursenames[] = format_string($c->fullname);
        }
    }
}


// Prix
$prices = $DB->get_records('subscription_plan_price', ['plan_id' => $planid], '', 'price, currency');
$formattedprices = [];
foreach ($prices as $p) {
    $formattedprices[] = sprintf('%s %s', number_format($p->price, 2), $p->currency);
}

// Réponse
echo json_encode([
    'name' => $translatedname ?: $planrecord->name,
    'description' => $translateddesc ?: '',
    'duration' => subscription_config::get_plans()[$planrecord->duration_key],
    'accessscope' => $scoperecord ? $scoperecord->name : null,
    'courses' => $coursenames,
    'prices' => $formattedprices,
]);
