<?php
// local/subscriptions/create_session.php
require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\domain\SubscriptionAdvisor;

// Pas d’output, pas de renderer : script d’action pur.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/subscriptions/stripe/create_session.php'));
$PAGE->set_pagelayout('base');

// use local_subscriptions\payment\stripe_helper;

global $DB, $USER;

$userid = (isloggedin() && !isguestuser()) ? (int)$USER->id : 0;

$planid    = required_param('planid', PARAM_INT);
$currency  = required_param('currency', PARAM_ALPHANUMEXT);
$currency  = core_text::strtolower($currency); // <-- important
$mode      = optional_param('mode', 'payment', PARAM_ALPHA); // 'payment' | 'subscription'
$email     = optional_param('email', '', PARAM_RAW_TRIMMED);
$firstname = optional_param('firstname', '', PARAM_NOTAGS);
$lastname  = optional_param('lastname', '', PARAM_NOTAGS);

$operation = optional_param('operation', 'purchase_new', PARAM_ALPHANUMEXT);
$refsubid  = optional_param('ref_subid', 0, PARAM_INT);

// Nouveau : montants forcés (pour Renouveler / Prolonger depuis my_subscriptions)
$overrideamount  = optional_param('override_amount', '', PARAM_RAW_TRIMMED);  // ex: "262.25"
$overridecurrency= optional_param('override_currency', '', PARAM_ALPHANUMEXT);// ex: "EUR"

$extra_json = optional_param('extra_json', '', PARAM_RAW_TRIMMED);


// Si fournis: on force devise et montant (pour one-shot)
if ($overridecurrency !== '') {
    $currency = strtoupper($overridecurrency);
}


// Vérifie le plan actif.
$plan = $DB->get_record('subscription_plan', ['id' => $planid, 'is_active' => 1], '*', MUST_EXIST);

// Récupère le prix dans la devise.
$price = (float)$DB->get_field('subscription_plan_price', 
    'price', 
        [
        'planid'   => $planid,
        'currency' => $currency,
    ],MUST_EXIST); // price en centimes.

// Montant par défaut = prix public du plan cible
$finalAmount = (float)$price;

// Si connecté et operation ≠ purchase_new : recalcul via Advisor (inclure toutes les variantes upgrade_*)
if ($userid && (
        in_array($operation, ['queue_future','upgrade_prorata'], true)
        || strpos($operation, 'upgrade_') === 0  // ← NEW : couvre upgrade_now_replace_chain (et future variantes)
    )) {
    $advised = SubscriptionAdvisor::advise_options($userid, $planid, $currency);
    foreach ($advised as $opt) {
        if (!empty($opt['key']) && $opt['key'] === $operation) {
            $finalAmount = (float)$opt['amount'];
            if (!$refsubid && !empty($opt['ref_subid'])) { $refsubid = (int)$opt['ref_subid']; }
            // si l’Advisor fournit extra, on le poussera via extra_json (facultatif)
            break;
        }
    }
}

// OVERRIDE montant/devise (Renouveler/Prolonger depuis my_subscriptions)
if ($overrideamount !== '') {
    $finalAmount = (float)unformat_float($overrideamount, true);
}

// Détermine l’utilisateur courant (0 pour invité).
$userid = (isloggedin() && !isguestuser()) ? $USER->id : 0;

// Crée la demande de paiement PENDING.
$paymentreq = new stdClass();
$paymentreq->planid          = $planid;
$paymentreq->userid          = $userid;
$paymentreq->email           = $email ?: ($USER->email ?? '');
$paymentreq->firstname       = $firstname ?: ($USER->firstname ?? '');
$paymentreq->lastname        = $lastname ?: ($USER->lastname ?? '');
$paymentreq->currency        = $currency;
$paymentreq->price           = (float)$finalAmount; // <= montant final
$paymentreq->status          = 'pending';
$paymentreq->creation_date   = time();
$paymentreq->last_update     = time();
$paymentreq->operation       = $operation;
$paymentreq->reference_subscription_id = $refsubid ?: null;

error_log('[subs][pr][create] op='.$operation.' user='.$userid.' plan='.$planid.' currency='.$currency.' finalAmount='.$finalAmount);


$pid = $DB->insert_record('subscription_payment_request', $paymentreq);
$paymentreq->id = $pid;

$token = bin2hex(random_bytes(32));
$expires = time() + 30 * 60; // 30 minutes

// Pose sur l'objet local (pour que la Gateway puisse l'utiliser si besoin)
$paymentreq->login_token          = $token;
$paymentreq->login_token_expires  = $expires;

$DB->update_record('subscription_payment_request', (object)[
    'id' => $paymentreq->id,
    'login_token' => $token,
    'login_token_expires' => $expires,
]);

$meta = [
    'op'    => $operation,
    'ref'   => $refsubid ?: null,
    'extra' => []
];
if ($extra_json !== '') {
    $decoded = json_decode($extra_json, true);
    if (is_array($decoded)) { $meta['extra'] = $decoded; }
}

$DB->update_record('subscription_payment_request', (object)[
    'id'            => $paymentreq->id,
    'operation'     => $operation,
    'reference_subscription_id' => $refsubid ?: null,
    'response_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
]);

$provider = 'stripe'; // pour l’instant on force Stripe
$gateway  = \local_subscriptions\payment\PaymentGatewayFactory::for($provider);

$success = new \moodle_url('/local/subscriptions/payment_success.php', [
    'pid' => $paymentreq->id,
    't'  => $paymentreq->login_token,
]);
$cancel  = new \moodle_url('/local/subscriptions/payment_cancel.php', [
    'pid' => $paymentreq->id,
]);

// Si abonnement récurrent, il te faut connaître le price_id Stripe du plan
$recurrent = !empty($plan->is_recurring);

$options = [
    'mode'         => ($recurrent ? 'subscription' : 'payment'),
    'product_name' => format_string($plan->name),
    'success_url'  => $success->out(false) . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'   => $cancel->out(false),
];

// ⚠️ Si abonnement récurrent -> il faut impérativement un price_id Stripe pour CETTE devise
if ($recurrent) {
    // Sécurité: recharger le prix pour la devise demandée, si ce n’est pas déjà fait
    if (empty($priceobj) || strtoupper($priceobj->currency) !== $currency) {
        $priceobj = $DB->get_record('subscription_plan_price',
            ['planid' => $planid, 'currency' => $currency], '*', MUST_EXIST);
    }

    $stripepriceid = $priceobj->stripe_price_id ?? '';
    if (empty($stripepriceid)) {
        // Log dev + message utilisateur propre
        debugging('[subs][create_session][error] Missing stripe_price_id for plan '.$planid.' currency '.$currency, DEBUG_DEVELOPER);

        // Redirige sur une page d’erreur utilisateur
        $err = new \moodle_url('/local/subscriptions/payment_error.php', [
            'code' => 'missing_stripe_price_id',
            'msg'  => rawurlencode('Ce plan récurrent n’est pas configuré pour la devise '.$currency.'.')
        ]);
        redirect($err);
        exit;
    }

    $options['price_map'] = ['stripe_price_id' => $stripepriceid];
} else {
    // One-shot : le montant vient de la PR (price/amount) -> rien à ajouter ici
}

try {
    $res = $gateway->create_checkout_session($paymentreq, $options);

    $DB->update_record('subscription_payment_request', (object)[
        'id'                  => $pid,
        'payment_provider'    => $provider,
        'sessionid'           => $res->provider_session_id,
    ]);

    redirect($res->redirect_url);

} catch (\Throwable $e) {
    // Gestion d’erreur (identique à ta logique actuelle)
    $paymentreq->status = 'error';
    $paymentreq->response_json = json_encode(['error' => $e->getMessage()]);
    $paymentreq->last_update = time();
    $DB->update_record('subscription_payment_request', $paymentreq);

    debugging('[subs][create_session][error] '.$e->getMessage(), DEBUG_DEVELOPER);
    $err = new \moodle_url('/local/subscriptions/payment_error.php', [
        'code' => 'session_create',
        'msg'  => rawurlencode($e->getMessage())
    ]);
    redirect($err);
}

