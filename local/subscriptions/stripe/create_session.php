<?php
// local/subscriptions/create_session.php
require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

// Pas d’output, pas de renderer : script d’action pur.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/subscriptions/stripe/create_session.php'));
$PAGE->set_pagelayout('base');

use local_subscriptions\payment\stripe_helper;

global $DB, $USER;

$planid   = required_param('planid', PARAM_INT);
$currency = required_param('currency', PARAM_ALPHANUMEXT);

// Données invité possibles.
$email     = optional_param('email', '', PARAM_RAW_TRIMMED);
$firstname = optional_param('firstname', '', PARAM_NOTAGS);
$lastname  = optional_param('lastname', '', PARAM_NOTAGS);

// Vérifie le plan actif.
$plan = $DB->get_record('subscription_plan', ['id' => $planid, 'is_active' => 1], '*', MUST_EXIST);

// Récupère le prix dans la devise.
$price = (float)$DB->get_field('subscription_plan_price', 
    'price', 
        [
        'planid'   => $planid,
        'currency' => core_text::strtolower($currency),
    ],MUST_EXIST); // price en centimes.

// Détermine l’utilisateur courant (0 pour invité).
$userid = (isloggedin() && !isguestuser()) ? $USER->id : 0;

// Crée la demande de paiement PENDING.
$paymentreq = new stdClass();
$paymentreq->planid          = $planid;
$paymentreq->userid          = $userid;
$paymentreq->email           = $email ?: ($USER->email ?? '');
$paymentreq->firstname       = $firstname ?: ($USER->firstname ?? '');
$paymentreq->lastname        = $lastname ?: ($USER->lastname ?? '');
$paymentreq->currency        = core_text::strtolower($currency);
$paymentreq->price           = $price;
$paymentreq->payment_provider= 'stripe';
$paymentreq->status          = 'pending';
$paymentreq->transactionid   = null;
$paymentreq->payment_link    = null; // rempli après
$paymentreq->response_json   = null;
$paymentreq->creation_date   = time();
$paymentreq->payment_date    = null;
$paymentreq->expiration_date = null;
$pid = $DB->insert_record('subscription_payment_request', $paymentreq);

// URLs de retour.
$successurl = $CFG->wwwroot . '/local/subscriptions/payment_success.php?session_id={CHECKOUT_SESSION_ID}';
$cancelurl  = new moodle_url('/local/subscriptions/payment_cancel.php', ['pid' => $pid]);

// Crée la session Stripe.
require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php');
\Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret'));

try {
    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'success_url' => $successurl,
        'cancel_url'  => $cancelurl->out(false),
        'customer_creation' => 'always',
        'customer_email' => $paymentreq->email ?: null,
        'line_items' => [[
            'price_data' => [
                'currency' => core_text::strtolower($currency),
                'product_data' => [
                    'name' => format_string($plan->name ?? 'Plan '.$planid),
                ],
                'unit_amount' => (int) round($price * 100), // en centimes
            ],
            'quantity' => 1,
        ]],
        'metadata' => [
            'payment_request_id' => (string)$pid,
            'planid'             => (string)$planid,
            'userid'             => (string)$userid,
            'email'              => (string)$paymentreq->email,
            'firstname'          => (string)$paymentreq->firstname,
            'lastname'           => (string)$paymentreq->lastname,
        ],

        // ⬇️ C’EST ICI qu’on met la metadata destinée au PaymentIntent
        'payment_intent_data' => [
            'metadata' => [
                'payment_request_id' => (string)$pid,
                'planid'             => (string)$planid,
                'userid'             => (string)$userid,
                'email'              => (string)$paymentreq->email,
                'firstname'          => (string)$paymentreq->firstname,
                'lastname'           => (string)$paymentreq->lastname,
            ],
        ],
    ]);

    // Mise à jour de la demande avec sessionid + url.
    $paymentreq->id          = $pid;
    $paymentreq->sessionid   = $session->id;
    $paymentreq->payment_link= $session->url;
    $paymentreq->response_json = json_encode($session);
    $DB->update_record('subscription_payment_request', $paymentreq);

    redirect($session->url);

} catch (\Throwable $e) {
    if (!empty($pid)) {
        $paymentreq->id = $pid;
        $paymentreq->status = 'error';
        $paymentreq->response_json = json_encode(['error'=>$e->getMessage()]);
        $DB->update_record('subscription_payment_request', $paymentreq);
    }
    $err = new moodle_url('/local/subscriptions/payment_error.php', [
        'code' => 'session_create',
        'msg'  => rawurlencode($e->getMessage())
    ]);
    header('Location: '.$err->out(false));
    exit;
}

