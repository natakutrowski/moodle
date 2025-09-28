<?php
// local/subscriptions/payment/create_session.php
require_once(dirname(__DIR__, 3) . '/config.php');


defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\Provider;
use local_subscriptions\payment\ProviderSelector;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\constants\PaymentReturn;
use local_subscriptions\url\UrlFactory;

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(UrlFactory::create_session());
$PAGE->set_pagelayout('base');

// ──────────────────────────────────────────────────────────────────────────
// 0) Entrées (agnostiques)
// ──────────────────────────────────────────────────────────────────────────
$planid     = required_param('planid',    PARAM_INT);
$operation  = required_param('operation', PARAM_ALPHANUMEXT); // purchase_new|queue_future|upgrade_now_replace_chain
$currency   = required_param('currency',  PARAM_ALPHANUMEXT);
$refsubid   = optional_param('ref_subid', 0, PARAM_INT);
$extra_json = optional_param('extra_json','', PARAM_RAW_TRIMMED);

// overrides éventuels
$providerOverride   = optional_param('provider', '', PARAM_ALPHANUMEXT);
$overrideamount     = optional_param('override_amount',   '', PARAM_RAW_TRIMMED);   // "262.25"
$overridecurrency   = optional_param('override_currency', '', PARAM_ALPHANUMEXT);   // "EUR"

// champs guest
$email     = optional_param('email', '', PARAM_RAW_TRIMMED);
$firstname = optional_param('firstname', '', PARAM_NOTAGS);
$lastname  = optional_param('lastname', '', PARAM_NOTAGS);

// montant forcé en minor (depuis un formulaire interne) — optionnel
$amountMinorOverride = optional_param('amount_minor', 0, PARAM_INT);

global $DB, $USER, $CFG;

// ──────────────────────────────────────────────────────────────────────────
// 1) Contexte plan & provider
// ──────────────────────────────────────────────────────────────────────────
$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);

$currency = strtoupper($overridecurrency !== '' ? $overridecurrency : $currency);

$provider = $providerOverride ? strtolower($providerOverride)
                              : ProviderSelector::chooseForPlan($plan, $currency, $USER->id ?? null);
if (!$provider) { $provider = Provider::defaultProvider(); }

// ──────────────────────────────────────────────────────────────────────────
/** Helper: calcule le prix "major" à payer pour cette PR (agnostique).
 *  Ordre: amount_minor override -> override_amount -> Advisor (si connecté et upgrade/queue) -> prix public.
 */
$compute_price_major = function() use ($DB, $USER, $planid, $currency, $operation, $refsubid, $overrideamount, $amountMinorOverride) : float {
    // 1) montant forcé en minor
    if (!empty($amountMinorOverride)) {
        return round(((int)$amountMinorOverride) / 100, 2);
    }
    // 2) override en major
    if ($overrideamount !== '') {
        return round((float)unformat_float($overrideamount, true), 2);
    }
    // 3) Advisor (si dispo) pour upgrade/queue
    $loggedin = (isloggedin() && !isguestuser());
    if ($loggedin && in_array($operation, ['queue_future','upgrade_now_replace_chain'], true)) {

        $advised = \local_subscriptions\domain\SubscriptionAdvisor::advise_options($USER->id, $planid, $currency);
        foreach ($advised as $opt) {
            if (!empty($opt['key']) && $opt['key'] === $operation) {
                // amount attendu en major
                $amount = (float)($opt['amount'] ?? 0);
                return round($amount, 2);
            }
        }
    }
    // 4) prix public du plan (table subscription_plan_price)
    $row = $DB->get_record('subscription_plan_price',
        ['planid' => $planid, 'currency' => $currency],
        'price',
        IGNORE_MISSING
    );
    if ($row && isset($row->price)) {
        return round((float)$row->price, 2);
    }
    throw new \moodle_exception('err_cannot_determine_price', 'local_subscriptions');
};

// ──────────────────────────────────────────────────────────────────────────
// 2) Ensure Payment Request ($pr)
// ──────────────────────────────────────────────────────────────────────────
$pr = null;

// (a) via ?pid=...
$pid = optional_param('pid', 0, PARAM_INT);
if ($pid) {
    $pr = $DB->get_record('subscription_payment_request', ['id' => $pid], '*', MUST_EXIST);
}

// (b) sinon créer la PR (PENDING)
if (!$pr) {
    $priceMajor = $compute_price_major();

    $pr = (object)[
        'planid'         => $planid,
        'userid'         => (isloggedin() && !isguestuser()) ? (int)$USER->id : null,
        'email'          => $email ?: ($USER->email ?? ''),
        'firstname'      => $firstname ?: ($USER->firstname ?? ''),
        'lastname'       => $lastname  ?: ($USER->lastname  ?? ''),
        'currency'       => $currency,
        'price'          => $priceMajor,          // major units
        'payment_provider' => $provider,
        'status'         => Status::PENDING,
        'creation_date'  => time(),
        'last_update'    => time(),
        'operation'      => $operation,
        'reference_subscription_id' => $refsubid ?: null,
        'response_json'  => $extra_json !== '' ? $extra_json : null,
    ];
    $pr->id = $DB->insert_record('subscription_payment_request', $pr);

    // login token (auto-login sur payment_success.php, TTL 30 min)
    $token   = bin2hex(random_bytes(32));
    $expires = time() + 30 * 60;

    $DB->update_record('subscription_payment_request', (object)[
        'id'                   => $pr->id,
        'login_token'          => $token,
        'login_token_expires'  => $expires,
    ]);
    $pr->login_token         = $token;
    $pr->login_token_expires = $expires;
} else {
    // hydrate infos guest si manquantes
    $upd = (object)['id' => $pr->id]; $need = false;
    if (empty($pr->email)      && $email)     { $pr->email     = $upd->email     = $email;     $need = true; }
    if (empty($pr->firstname)  && $firstname) { $pr->firstname = $upd->firstname = $firstname; $need = true; }
    if (empty($pr->lastname)   && $lastname)  { $pr->lastname  = $upd->lastname  = $lastname;  $need = true; }
    if ($need) { $DB->update_record('subscription_payment_request', $upd); }
}

// ──────────────────────────────────────────────────────────────────────────
// 3) Construire options pour le gateway (agnostique + léger spécifique)
// ──────────────────────────────────────────────────────────────────────────
$options = [
    // communs pour logs et pricing
    'operation'     => $operation,
    'ref_sub_id'    => $refsubid ?: null,
    'extra'         => $extra_json ? (json_decode($extra_json, true) ?: []) : [],
    'amount_minor'  => !empty($amountMinorOverride) ? (int)$amountMinorOverride : null, // si utile côté gateway
    'email'         => $pr->email ?: null,
    'firstname'     => $pr->firstname ?: null,
    'lastname'      => $pr->lastname ?: null,
    'product_name'  => format_string($plan->name, true, ['context' => \context_system::instance()]),
];

// URLs de retour selon provider
if ($provider === Provider::ALFA) {

    $returnurl  = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::ALFA,
        'result' => PaymentReturn::SUCCESS,
    ]))->out(false);

    $failurl  = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::ALFA,
        'result' => PaymentReturn::CANCEL,
    ]))->out(false);

    $options += [
        'returnurl' => $returnurl,
        'failurl'   => $failurl,
        'language'  => current_language() === 'ru' ? 'ru' : 'en',
    ];
} else if ($provider === Provider::STRIPE) {
    $success = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::STRIPE,
        'result' => PaymentReturn::SUCCESS,
        't' => $pr->login_token,
    ]))->out(false) . '&session_id={CHECKOUT_SESSION_ID}';

    $cancel  = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::STRIPE,
        'result' => PaymentReturn::CANCEL,
    ]))->out(false);

    $options += [
        'success_url' => $success,
        'cancel_url'  => $cancel,
        // Laisse le gateway Stripe décider du mode si tu préfères.
        'mode'        => !empty($plan->is_recurring) ? 'subscription' : 'payment',
    ];

    // Si abonnement récurrent: passer le price_id Stripe du plan/devise
    if (!empty($plan->is_recurring)) {
        $priceobj = $DB->get_record('subscription_plan_price',
            ['planid' => $planid, 'currency' => $currency], '*', MUST_EXIST);
        if (!empty($priceobj->stripe_price_id)) {
            $options['price_map'] = ['stripe_price_id' => $priceobj->stripe_price_id];
        }
    }
}

// ──────────────────────────────────────────────────────────────────────────
// 4) Appel gateway & redirection
// ──────────────────────────────────────────────────────────────────────────
$gateway = PaymentGatewayFactory::for($provider);

try {
    $result = $gateway->create_checkout_session($pr, $options);

    // Tenter de mémoriser une session provider si fournie par le DTO
    if (is_object($result)) {
        if (property_exists($result, 'provider_session_id') && !empty($result->provider_session_id)) {
            $DB->update_record('subscription_payment_request', (object)[
                'id'               => $pr->id,
                'payment_provider' => $provider,
                'sessionid'        => (string)$result->provider_session_id,
            ]);
        }
    }

    // URL de redirection (robuste)
    $url = '';
    if (is_string($result)) {
        $url = $result;
    } elseif (is_array($result)) {
        $url = $result['redirect_url'] ?? $result['url'] ?? '';
    } elseif (is_object($result)) {
        if (property_exists($result, 'redirect_url'))       { $url = $result->redirect_url; }
        elseif (method_exists($result, 'getUrl'))             { $url = $result->getUrl(); }
        elseif (method_exists($result, 'get_redirect_url'))   { $url = $result->get_redirect_url(); }
    }

    if (empty($url)) {
        throw new \moodle_exception('err_no_redirect_url', 'local_subscriptions');
    }

    redirect(new moodle_url($url));

} catch (\Throwable $e) {
    // Log & UX
    $DB->update_record('subscription_payment_request', (object)[
        'id'         => $pr->id,
        'last_error' => $e->getMessage(),
        'status'     => PaymentReturn::ERROR,
        'last_update'=> time(),
    ]);
    redirect(UrlFactory::payment_error([
        'code' => 'session_create',
        'msg'  => $e->getMessage(),
    ]));
    exit;
}
