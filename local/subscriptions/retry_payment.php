<?php
// local/subscriptions/retry_payment.php
require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/subscriptions/vendor/autoload.php');
require_once($CFG->dirroot.'/local/subscriptions/lib.php');

global $DB, $CFG;

$pid   = required_param('pid', PARAM_INT);
$token = required_param('t', PARAM_ALPHANUMEXT);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/subscriptions/retry_payment.php', ['pid'=>$pid]));
$PAGE->set_pagelayout('base'); // pas de rendu, on redirige

$pr = $DB->get_record('subscription_payment_request', ['id'=>$pid], '*', MUST_EXIST);

// Validate status
if (!in_array($pr->status, ['pending','failed','canceled','expired','error'])) {
    redirect(new moodle_url('/local/subscriptions/subscribe.php'), get_string('retry_invalid_status', 'local_subscriptions'), 5, \core\output\notification::NOTIFY_ERROR);
}

// Validate token & expiry
if (empty($pr->retry_token) || $pr->retry_token !== $token || ($pr->retry_expires && time() > $pr->retry_expires)) {
    redirect(new moodle_url('/local/subscriptions/subscribe.php'), get_string('retry_link_expired', 'local_subscriptions'), 5, \core\output\notification::NOTIFY_ERROR);
}

// Rebuild session
\Stripe\Stripe::setApiKey(get_config('local_subscriptions','stripe_secret'));

$plan   = $DB->get_record('subscription_plan', ['id'=>$pr->planid], '*', MUST_EXIST);
$priceD = (float)$pr->price;
$unit   = (int)round($priceD*100);

$success = $CFG->wwwroot . '/local/subscriptions/payment_success.php?session_id={CHECKOUT_SESSION_ID}';
$cancel  = (new moodle_url('/local/subscriptions/payment_cancel.php', ['pid'=>$pid]))->out(false);

$session = \Stripe\Checkout\Session::create([
    'mode' => 'payment',
    'success_url' => $success,
    'cancel_url'  => $cancel,
    'customer_creation' => 'always',
    'customer_email'    => $pr->email ?: null,
    'line_items' => [[
        'price_data' => [
            'currency' => $pr->currency,
            'product_data' => ['name' => s($plan->name ?? ('Plan '.$pr->planid))],
            'unit_amount' => $unit,
        ],
        'quantity' => 1,
    ]],
    'metadata' => [
        'payment_request_id' => (string)$pid,
        'planid'   => (string)$pr->planid,
        'userid'   => (string)($pr->userid ?? 0),
        'email'    => (string)($pr->email ?? ''),
        'firstname'=> (string)($pr->firstname ?? ''),
        'lastname' => (string)($pr->lastname ?? ''),
    ],

    // ⬇️ C’EST ICI qu’on met la metadata destinée au PaymentIntent
    'payment_intent_data' => [
        'metadata' => [
            'payment_request_id' => (string)$pid,
            'planid'   => (string)$pr->planid,
            'userid'   => (string)($pr->userid ?? 0),
            'email'    => (string)($pr->email ?? ''),
            'firstname'=> (string)($pr->firstname ?? ''),
            'lastname' => (string)($pr->lastname ?? ''),
        ],
    ],
]);

// Update PR
$pr->sessionid   = $session->id;
$pr->payment_link= $session->url;
$pr->status      = 'pending';
$pr->attempts    = (int)$pr->attempts + 1;
$pr->last_attempt= time();
$DB->update_record('subscription_payment_request', $pr);

redirect($session->url);
