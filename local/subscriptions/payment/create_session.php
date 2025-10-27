<?php
// local/subscriptions/payment/create_session.php
require_once(dirname(__DIR__, 3) . '/config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');

// === CSRF obligatoire (à coller ici) ====================================
require_sesskey();
// =======================================================================

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\Provider;
use local_subscriptions\payment\ProviderSelector;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\constants\PaymentReturn;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Operation;

\local_subscriptions\subscription_config::guard_public_access();

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

$accept_privacy = required_param('accept_privacy', PARAM_BOOL);
$accept_terms   = required_param('accept_terms',   PARAM_BOOL);


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

// ──────────────────────────────────────────────────────────────────────────
// 0bis) Mode invité forcé (triallimited / force_guest / non connecté)
// ──────────────────────────────────────────────────────────────────────────
$forceguest = optional_param('force_guest', 0, PARAM_BOOL);
$istripossible = function_exists('local_campus_is_trial_user');
if (!$istripossible) {
    // tentative douce de charger la fonction depuis local_campus/lib.php ; sinon fallback (voir §3)
    $comp = \core_component::get_component_directory('local_campus');
    if ($comp && file_exists($comp.'/lib.php')) {
        require_once($comp.'/lib.php');
        $istripossible = function_exists('local_campus_is_trial_user');
    }
}
$istrial  = $istripossible ? (bool) local_campus_is_trial_user() : false;
$asguest  = $forceguest || $istrial || isguestuser() || !isloggedin();

// Si invité forcé → champs requis depuis le formulaire
if ($asguest) {
    $email     = required_param('email', PARAM_EMAIL);
    $firstname = required_param('firstname', PARAM_NOTAGS);
    $lastname  = required_param('lastname', PARAM_NOTAGS);
}

if ($asguest && $operation !== Operation::PURCHASE_NEW) {
    $operation = Operation::PURCHASE_NEW;
}


// === Verrouillage overrides (à coller ici) ==============================
// Uniquement l’admin peut utiliser provider/override_amount/amount_minor/etc.
$isadmin = is_siteadmin();
$hasoverride = ($providerOverride !== '' || $overrideamount !== '' ||
                $overridecurrency !== '' || !empty($amountMinorOverride));

if (!$isadmin && $hasoverride) {
    // Public : on ignore tout override dangereux
    $providerOverride    = '';
    $overrideamount      = '';
    $overridecurrency    = '';
    $amountMinorOverride = 0;
    $hasoverride         = false;
}

// Liste blanche des opérations
if (!in_array($operation, [
    Operation::PURCHASE_NEW,
    Operation::QUEUE_FUTURE,
    Operation::UPGRADE_NOW_REPLACE_CHAIN
], true)) {
    throw new \moodle_exception('invalid_operation', 'local_subscriptions');
}
// =======================================================================


global $DB, $USER;

// ──────────────────────────────────────────────────────────────────────────
// 1) Contexte plan & provider
// ──────────────────────────────────────────────────────────────────────────
$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);

$currency = strtoupper($overridecurrency !== '' ? $overridecurrency : $currency);

$provider = $providerOverride ? strtolower($providerOverride)
                              : ProviderSelector::chooseForPlan($plan, $currency, $asguest ? null : ($USER->id ?? null));
if (!$provider) { $provider = Provider::defaultProvider(); }

// Si Alfa => devise **toujours** RUB côté PSP
if ($provider === Provider::ALFA) {
    $currency = 'RUB';
}

// === Calcul prix major (remplace ta closure) ============================
$compute_price_major = function() use (
    $DB, $USER, $planid, $currency, $operation,
    $overrideamount, $amountMinorOverride, $asguest, $hasoverride
): float {
    // 1) montant forcé en minor (admin uniquement)
    if ($hasoverride && !empty($amountMinorOverride)) {
        return round(((int)$amountMinorOverride) / 100, 2);
    }
    // 2) override en major (admin uniquement)
    if ($hasoverride && $overrideamount !== '') {
        return round((float)unformat_float($overrideamount, true), 2);
    }
    // 3) Advisor (upgrade/queue pour utilisateur connecté)
    $loggedin = (!$asguest && isloggedin() && !isguestuser());
    if ($loggedin && in_array($operation, [Operation::QUEUE_FUTURE,Operation::UPGRADE_NOW_REPLACE_CHAIN], true)) {
        $advised = \local_subscriptions\domain\SubscriptionAdvisor::advise_options($USER->id, $planid, $currency);
        foreach ($advised as $opt) {
            if (!empty($opt['key']) && $opt['key'] === $operation) {
                return round((float)($opt['amount'] ?? 0), 2);
            }
        }
    }
    // 4) prix public configuré (obligatoire)
    return local_subscriptions_get_config_price($planid, $currency);
};
// =======================================================================


// ──────────────────────────────────────────────────────────────────────────
// 2) Ensure Payment Request ($pr)
// ──────────────────────────────────────────────────────────────────────────
$pr = null;

// (a) via ?pid=...
$pid = optional_param('pid', 0, PARAM_INT);
if ($pid) {
    $pr = $DB->get_record('subscription_payment_request', ['id' => $pid], '*', MUST_EXIST);

    // === Vérifs PR existante (à ajouter dans if ($pid)) =====================
    if ($pr->status !== Status::PENDING) {
        throw new \moodle_exception('invalid_payment_request_status', 'local_subscriptions');
    }
    if (!empty($pr->userid)) {
        if (!isloggedin() || isguestuser() || (int)$pr->userid !== (int)$USER->id) {
            throw new \moodle_exception('invalid_payment_request_owner', 'local_subscriptions');
        }
    }
    // Aligner la devise avec la PR si définie
    if (!empty($pr->currency)) {
        $currency = strtoupper($pr->currency);
    }
    // =======================================================================


}

// (b) sinon créer la PR (PENDING)
if (!$pr) {
    $priceMajor = $compute_price_major();

    // Si Alfa => devise RUB et prix configuré RUB (aucun override)
    if ($provider === Provider::ALFA) {
        $currency   = 'RUB';
        if ($operation === Operation::PURCHASE_NEW || $operation === Operation::QUEUE_FUTURE) {
            $priceMajor = local_subscriptions_get_config_price($planid, $currency);
        }
    }

    // Achat simple : revalider contre le prix configuré
    if ($operation === Operation::PURCHASE_NEW || $operation === Operation::QUEUE_FUTURE) {
        $cfgPrice = local_subscriptions_get_config_price($planid, $currency);
        if (abs($priceMajor - $cfgPrice) > 0.01) {
            $priceMajor = $cfgPrice;
        }
    }

    // … juste avant la construction de $pr :
    $ip = function_exists('getremoteaddr') ? getremoteaddr() : ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === 'UNKNOWN') { $ip = ''; }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ua = $ua !== '' ? mb_substr($ua, 0, 65535) : null; // TEXT safe

    $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $al = $al !== '' ? mb_substr($al, 0, 191) : null;   // CHAR(191)

    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $ref = $ref !== '' ? mb_substr($ref, 0, 65535) : null; // TEXT    

    $pr = (object)[
        'planid'         => $planid,
        'userid'         => $asguest ? null : ((isloggedin() && !isguestuser()) ? (int)$USER->id : null),
        'email'          => $asguest ? $email     : ($USER->email     ?? $email),
        'firstname'      => $asguest ? $firstname : ($USER->firstname ?? $firstname),
        'lastname'       => $asguest ? $lastname  : ($USER->lastname  ?? $lastname),
        'currency'       => $currency,
        'price'          => $priceMajor,          // major units
        'payment_provider' => $provider,
        'status'         => Status::PENDING,
        'creation_date'  => time(),
        'last_update'    => time(),
        'operation'      => $operation,
        'reference_subscription_id' => $refsubid ?: null,
        'response_json'  => $extra_json !== '' ? $extra_json : null,

        // --- Nouveau : traçage visitor ---
        'created_ip'         => $ip ?: null,
        'created_useragent'  => $ua,              // TEXT (null si vide)
        'accept_language'    => $al ?: null,      // CHAR(191)
        'http_referer'       => $ref ?: null,     // TEXT
    ];
    $pr->id = $DB->insert_record('subscription_payment_request', $pr);

    // Sanity check final avant d’aller chez le PSP
    $configuredNow = local_subscriptions_get_config_price($planid, $currency);
    if (($operation === Operation::PURCHASE_NEW || $operation === Operation::QUEUE_FUTURE)
        && abs($pr->price - $configuredNow) > 0.01) {
        $pr->price = $configuredNow;
        $DB->update_record('subscription_payment_request', (object)['id'=>$pr->id, 'price'=>$configuredNow]);
    }    

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

    // === amount_minor sûr avant appel gateway (à coller juste avant l'appel) =
    $major = (float)$pr->price;
    $safeCurrency = ($provider === Provider::ALFA) ? 'RUB' : $currency;
    $options['amount_minor'] = local_subs_money_to_minor_units(number_format($major, 2, '.', ''), $safeCurrency);

    // Sanity Alfa : devise obligatoirement RUB
    if ($provider === Provider::ALFA && $safeCurrency !== 'RUB') {
        throw new \moodle_exception('invalid_currency_for_alfa', 'local_subscriptions');
    }
    // =======================================================================

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
