<?php
// local/subscriptions/portal.php
require_once(__DIR__ . '/../../config.php');

require_login(); // page protégée

$subid   = optional_param('subid', 0, PARAM_INT); // id user_subscription (optionnel)
$return  = optional_param('returnurl', '', PARAM_RAW_TRIMMED); // optionnel

$systemctx = context_system::instance();
$PAGE->set_context($systemctx);
$PAGE->set_url(new moodle_url('/local/subscriptions/portal.php', ['subid' => $subid]));
$PAGE->set_pagelayout('base');

global $DB, $USER, $CFG;

// 1) Retrouver une souscription de l'utilisateur pour extraire le customer Stripe
$params = ['userid' => $USER->id, 'provider' => 'stripe'];

// a) Privilégier celle passée en ?subid=
$sub = null;
if ($subid) {
    $sub = $DB->get_record('user_subscription', ['id' => $subid, 'userid' => $USER->id], '*', IGNORE_MISSING);
}
// b) Sinon, prendre une souscription Stripe "active" la plus récente
if (!$sub) {
    $sub = $DB->get_record_sql(
        "SELECT *
           FROM {user_subscription}
          WHERE userid = :userid
            AND payment_provider = :provider
            AND provider_customer_id IS NOT NULL
       ORDER BY last_update DESC
          LIMIT 1",
        $params,
        IGNORE_MISSING
    );
}

if (!$sub || empty($sub->provider_customer_id)) {
    // Pas de customer_id connu → message doux et sortie
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('portal_no_customer', 'local_subscriptions'), \core\output\notification::NOTIFY_WARNING);
    echo html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/profile.php'),
        get_string('view_my_subscriptions', 'local_subscriptions')), 'mt-3');
    echo $OUTPUT->footer();
    exit;
}

// 2) Créer une session Customer Portal
require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php'); // ou /vendor/autoload.php si global
$stripe_secret = get_config('local_subscriptions', 'stripe_secret_key') ?? get_config('local_subscriptions', 'stripe_secret');
if (empty($stripe_secret)) {
    throw new moodle_exception('configmissing', 'local_subscriptions', '', 'stripe_secret_key');
}
\Stripe\Stripe::setApiKey($stripe_secret);

$returnurl = !empty($return) ? $return : (new moodle_url('/user/my_subscriptions.php'))->out(false);

// Option: configuration du portail (pc_...) lue depuis la config plugin
$portalconfig = get_config('local_subscriptions', 'stripe_portal_configuration_id'); // ex. pc_123...
$params = [
    'customer'   => $sub->provider_customer_id, // cus_...
    'return_url' => $returnurl,
];
if (!empty($portalconfig)) {
    $params['configuration'] = $portalconfig;  // si configurée, on la force
}

try {
    $session = \Stripe\BillingPortal\Session::create($params);
    redirect($session->url);
} catch (\Stripe\Exception\InvalidRequestException $ex) {
    // Message propre pour l'utilisateur + consigne admin en log
    debugging('[portal] Stripe portal error: '.$ex->getMessage(), DEBUG_DEVELOPER);
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('portal_error_config', 'local_subscriptions'),
        \core\output\notification::NOTIFY_ERROR
    );
    echo html_writer::div(
        html_writer::link(new moodle_url('/user/my_subscriptions.php'), get_string('view_my_subscriptions', 'local_subscriptions')),
        'mt-3'
    );
    echo $OUTPUT->footer();
    exit;
}

