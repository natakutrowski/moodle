<?php
define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once(__DIR__.'/lib.php');

global $PAGE;
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url($_SERVER['PHP_SELF']));


header('Content-Type: application/json; charset=utf-8');

try {
    $redirectid = required_param('redirectid', PARAM_INT);

    // Cours autorisé ?
    $trialids = local_campus_trial_course_ids();
    if (!$trialids || !in_array($redirectid, $trialids, true)) {
        throw new moodle_exception('invalidcourseid');
    }

    $c = local_campus_get_cookie();
    if (!$c || $c['expiresat'] < time()) {
        echo json_encode(['status'=>'needs_form']); exit;
    }

    $trial = $DB->get_record('local_campus_trial', ['id'=>$c['trialid']]);
    // Cookie invalide ou expiré ?
    if (!$trial || (int)$trial->expiresat < time()) {
        local_campus_clear_cookie();
        echo json_encode([
            'status'    => 'expired',
            'subscribe' => (new moodle_url('/boutique'))->out(false)
        ]);
        exit;
    }

    // Si l'utilisateur est DEJA connecté et correspond au trial -> OK redirect
    if (isloggedin() && !isguestuser() && !empty($trial->userid) && (int)$trial->userid === (int)$USER->id) {
        $url = (new moodle_url('/course/view.php', ['id'=>$redirectid]))->out(false);
        echo json_encode(['status'=>'ok','redirect'=>$url]); exit;
    }

    // Sinon, pas d'auto-login -> on demande le formulaire
    echo json_encode(['status'=>'needs_form']); exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
