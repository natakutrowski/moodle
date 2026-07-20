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
use local_subscriptions\payment\PaymentFailureReporter;
use local_subscriptions\constants\PaymentReturn;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Operation;
use local_subscriptions\constants\Status;


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

$accept_terms   = required_param('accept_terms',   PARAM_BOOL);

$embedded = optional_param('embedded', 0, PARAM_BOOL);


// overrides éventuels
$providerOverride   = optional_param('provider', '', PARAM_ALPHANUMEXT);
$overrideamount     = optional_param('override_amount',   '', PARAM_RAW_TRIMMED);   // "262.25"
$overridecurrency   = optional_param('override_currency', '', PARAM_ALPHANUMEXT);   // "EUR"

// champs guest
$email     = optional_param('email', '', PARAM_RAW_TRIMMED);
$firstname = optional_param('firstname', '', PARAM_NOTAGS);
$lastname  = optional_param('lastname', '', PARAM_NOTAGS);
// champs téléphone / mot de passe (optionnels)
$phone       = optional_param('phone', '', PARAM_RAW_TRIMMED);
$phonecountry = optional_param('phonecountry', '', PARAM_ALPHANUMEXT);
$password    = optional_param('password', '', PARAM_RAW);



// montant forcé en minor (depuis un formulaire interne) — optionnel
$amountMinorOverride = optional_param('amount_minor', 0, PARAM_INT);

$enforceloginifknown = optional_param('enforce_login_if_known', 0, PARAM_BOOL);

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
//$asguest  = $forceguest || $istrial || isguestuser() || !isloggedin();
$asguest  = $forceguest || isguestuser() || !isloggedin();
$isrealguest = $asguest && !$istrial && !$forceguest;

// Si invité forcé → champs requis depuis le formulaire
if ($asguest) {
    $email     = required_param('email', PARAM_EMAIL);
    $firstname = required_param('firstname', PARAM_NOTAGS);
    $lastname  = required_param('lastname', PARAM_NOTAGS);

    // Pour les vrais invités (non trial, non forceguest), on exige un mot de passe
    if ($isrealguest) {
        global $CFG;

        $password = required_param('password', PARAM_RAW);

        if (core_text::strlen($password) < 8) {
            throw new moodle_exception('trial_password_min', 'local_campus');
        }

        if (!empty($CFG->passwordpolicy) && function_exists('check_password_policy')) {
            $errmsg = null;
            if (!check_password_policy($password, $errmsg, null)) {
                throw new moodle_exception('trial_password_policy_error', 'local_campus', '', (string)(html_to_text($errmsg) ?? ''));
            }
        }
    }
}


if ($asguest && $operation !== Operation::PURCHASE_NEW) {
    $operation = Operation::PURCHASE_NEW;
}

// --- Enforce login si l'email correspond à un compte existant -------------
if ($asguest && $enforceloginifknown && !empty($email)) {
    $norm = core_text::strtolower(trim($email));
    $u = $DB->get_record_sql(
        "SELECT id FROM {user} WHERE LOWER(email) = :e AND deleted = 0",
        ['e' => $norm]
    );
    if ($u) {
        // Retournera sur le même checkout une fois connecté.
        $return = new moodle_url('/local/subscriptions/checkout.php', [
            'planid'   => $planid,
            'currency' => $currency,
        ]);
        redirect(
            new moodle_url('/login/index.php', ['returnurl' => $return->out(false)]),
            get_string('existing_account_login_first', 'local_subscriptions'),
            0,
            \core\output\notification::NOTIFY_WARNING
        );
    }
}
// --------------------------------------------------------------------------


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

    // --- LOCK SNAPSHOT pour PR existante (si absent) ---
    if (empty($pr->locked_final_price) || (float)$pr->locked_final_price <= 0) {
        require_once($CFG->dirroot.'/local/subscriptions/classes/pricing_manager.php');
        require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

        $cur = strtoupper(trim($pr->currency ?? $currency ?? 'EUR'));
        $op  = $pr->operation ?? $operation;

        if (in_array($op, [Operation::UPGRADE_NOW_REPLACE_CHAIN, Operation::QUEUE_FUTURE], true)) {
            // 🔒 UPGRADE/QUEUE : on locke le montant CONSEILLÉ (PR->price)
            $final = round((float)($pr->price ?? 0), 2);

            // Fallback si PR->price absent : on recalcule via Advisor
            if ($final <= 0 && !empty($pr->userid)) {
                $advised = \local_subscriptions\domain\SubscriptionAdvisor::advise_options((int)$pr->userid, (int)$pr->planid, $cur);
                foreach ($advised as $opt) {
                    if (!empty($opt['key']) && $opt['key'] === Operation::UPGRADE_NOW_REPLACE_CHAIN) {
                        $final = round((float)($opt['amount'] ?? 0), 2);
                        break;
                    }
                }
            }
            if ($final <= 0) {
                // Dernier filet : tombe sur le prix public
                $final = local_subscriptions_get_config_price((int)$pr->planid, $cur);
            }

            $list    = local_subscriptions_get_config_price((int)$pr->planid, $cur);
            $discAmt = max(0.0, round($list - $final, 2));
            $discPct = ($list > 0 && $discAmt > 0) ? (int)round(($discAmt / $list) * 100) : 0;
            $reason  = $discAmt > 0 ? 'upgrade_diff' : null;

            $payable = [
                'list_price'       => $list,
                'discount_percent' => $discPct,
                'discount_amount'  => $discAmt,
                'final_price'      => $final,
                'discount_reason'  => $reason,
            ];
        } else {
            // Achat standard : calcule via pricing_manager (gère la remise essai)
            if (!empty($pr->userid)) {
                $payable = \local_subscriptions\pricing_manager::compute_payable((int)$pr->userid, (int)$pr->planid, $cur);
            } else {
                // Visiteur : pas de remise essai
                $list = local_subscriptions_get_config_price((int)$pr->planid, $cur);
                $payable = [
                    'list_price'       => $list,
                    'discount_percent' => 0,
                    'discount_amount'  => 0.0,
                    'final_price'      => $list,
                    'discount_reason'  => null,
                ];
            }
        }

        $pr->locked_list_price        = $payable['list_price'];
        $pr->locked_discount_percent  = $payable['discount_percent'];
        $pr->locked_discount_amount   = $payable['discount_amount'];
        $pr->locked_discount_reason   = $payable['discount_reason'];
        $pr->locked_final_price       = $payable['final_price'];
        $pr->locked_at                = time();
        $pr->currency                 = $cur;
        $pr->amount_minor             = (int) round($pr->locked_final_price * 100);

        $DB->update_record('subscription_payment_request', $pr);
    }


}

// (b) sinon créer la PR (PENDING)
if (!$pr) {
    $priceMajor = $compute_price_major();

    // Si Alfa => devise RUB et prix configuré RUB (aucun override)
    if ($provider === Provider::ALFA) {
        $currency   = 'RUB';
        if ($operation === Operation::PURCHASE_NEW) {
            $priceMajor = local_subscriptions_get_config_price($planid, $currency);
        }
    }

    // Achat simple : revalider contre le prix configuré
    if ($operation === Operation::PURCHASE_NEW) {
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
    $ref = $ref !== '' ? mb_substr($ref, 0, 65535) : null; // TEXT    // Fusionne extra_json (upgrade, etc.) + infos utilisateur côté checkout
    
    $meta = [];
    if ($extra_json !== '') {
        $tmp = json_decode($extra_json, true);
        if (is_array($tmp)) {
            $meta = $tmp;
        }
    }

    if ($asguest) {
        $userExtra = [];

        if ($phonecountry !== '') {
            $userExtra['phonecountry'] = $phonecountry;
        }
        if ($phone !== '') {
            $userExtra['phone'] = $phone;
        }
        if ($isrealguest && $password !== '') {
            $userExtra['password_hash'] = hash_internal_user_password($password);
        }

        if ($userExtra) {
            $meta['checkout_user'] = $userExtra;
        }
    }

    $response_json = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;



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
        'response_json'  => $response_json,

        // --- Nouveau : traçage visitor ---
        'created_ip'         => $ip ?: null,
        'created_useragent'  => $ua,              // TEXT (null si vide)
        'accept_language'    => $al ?: null,      // CHAR(191)
        'http_referer'       => $ref ?: null,     // TEXT
        'phone'          => $phone !== '' ? $phone : null,
        'phone_country'  => $phonecountry !== '' ? $phonecountry : null,
    ];

    $pr->id = $DB->insert_record('subscription_payment_request', $pr);

    // === LOCK SNAPSHOT (avant l'appel gateway) ==========================
    require_once($CFG->dirroot.'/local/subscriptions/classes/pricing_manager.php');
    require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

    $cur = strtoupper(trim($pr->currency ?? $currency ?? 'EUR'));
    $op  = $pr->operation ?? $operation;

    if (in_array($op, [Operation::UPGRADE_NOW_REPLACE_CHAIN, Operation::QUEUE_FUTURE], true)) {
        // 🔒 UPGRADE/QUEUE : on part du montant PR->price calculé plus haut ($priceMajor)
        $final = round((float)($pr->price ?? 0), 2);
        if ($final <= 0 && !empty($pr->userid)) {
            $advised = \local_subscriptions\domain\SubscriptionAdvisor::advise_options((int)$pr->userid, (int)$pr->planid, $cur);
            foreach ($advised as $opt) {
                if (!empty($opt['key']) && $opt['key'] === Operation::UPGRADE_NOW_REPLACE_CHAIN) {
                    $final = round((float)($opt['amount'] ?? 0), 2);
                    break;
                }
            }
        }
        if ($final <= 0) {
            $final = local_subscriptions_get_config_price((int)$pr->planid, $cur);
        }

        $list    = local_subscriptions_get_config_price((int)$pr->planid, $cur);
        $discAmt = max(0.0, round($list - $final, 2));
        $discPct = ($list > 0 && $discAmt > 0) ? (int)round(($discAmt / $list) * 100) : 0;
        $reason  = $discAmt > 0 ? 'upgrade_diff' : null;

        $payable = [
            'list_price'       => $list,
            'discount_percent' => $discPct,
            'discount_amount'  => $discAmt,
            'final_price'      => $final,
            'discount_reason'  => $reason,
        ];
    } else {
        // Achat standard (remise essai éventuelle)
        if (!empty($pr->userid)) {
            $payable = \local_subscriptions\pricing_manager::compute_payable((int)$pr->userid, (int)$pr->planid, $cur);
        } else {
            $list = local_subscriptions_get_config_price((int)$pr->planid, $cur);
            $payable = [
                'list_price'       => $list,
                'discount_percent' => 0,
                'discount_amount'  => 0.0,
                'final_price'      => $list,
                'discount_reason'  => null,
            ];
        }
    }

    // Alimente la PR avec le LOCK
    $pr->locked_list_price        = $payable['list_price'];
    $pr->locked_discount_percent  = $payable['discount_percent'];
    $pr->locked_discount_amount   = $payable['discount_amount'];
    $pr->locked_discount_reason   = $payable['discount_reason'];
    $pr->locked_final_price       = $payable['final_price'];
    $pr->locked_at                = time();
    $pr->currency                 = $cur;
    $pr->amount_minor             = (int) round($pr->locked_final_price * 100);

    $DB->update_record('subscription_payment_request', $pr);
    // ====================================================================


    // Sanity check final avant d’aller chez le PSP
    $configuredNow = local_subscriptions_get_config_price($planid, $currency);
    if (($operation === Operation::PURCHASE_NEW)
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
    if (empty($pr->phone)      && $phone)        { $pr->phone        = $upd->phone         = $phone;        $need = true; }
    if (empty($pr->phone_country) && $phonecountry) { $pr->phone_country = $upd->phone_country = $phonecountry; $need = true; }
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

$uilang = optional_param('uilang','', PARAM_ALPHANUMEXT);
if ($uilang === '') {
    $uilang = strtolower(substr((string)($SESSION->lang ?? $USER->lang ?? $CFG->lang ?? (get_config('defaultuserlang','local_subscriptions') ?? 'ru')), 0, 2));
}
if (!in_array($uilang, ['fr','en','ru'], true)) { $uilang = 'ru'; }

// URLs de retour selon provider
if ($provider === Provider::ALFA) {

    $returnurl  = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::ALFA,
        'result' => PaymentReturn::SUCCESS,
        'uilang'     => $uilang,
        'embedded' => $embedded ? 1 : 0,
    ]))->out(false);

    $failurl  = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::ALFA,
        'result' => PaymentReturn::CANCEL,
        'uilang'     => $uilang,
        'embedded' => $embedded ? 1 : 0,
    ]))->out(false);

    $options += [
        'returnurl' => $returnurl,
        'failurl'   => $failurl,
        'language'  => ($uilang === 'ru') ? 'ru' : 'en', // UI Alfa
    ];
} else if ($provider === Provider::STRIPE) {
    $success = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::STRIPE,
        'result' => PaymentReturn::SUCCESS,
        't' => $pr->login_token,
        'uilang'     => $uilang,
        'embedded' => $embedded ? 1 : 0,
    ]))->out(false) . '&session_id={CHECKOUT_SESSION_ID}';

    $cancel  = (UrlFactory::return([
        'pid' => $pr->id,
        'provider' => Provider::STRIPE,
        'result' => PaymentReturn::CANCEL,
        'uilang'     => $uilang,
        'embedded' => $embedded ? 1 : 0,
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

// --- Forcer le paiement au montant LOCK dans certains cas ---
$uselock = (isset($pr->locked_final_price) && (float)$pr->locked_final_price > 0);

// On force le mode 'payment' (montant fixe) si :
// - UPGRADE_NOW_REPLACE_CHAIN (on encaisse la différence maintenant) OU
// - QUEUE_FUTURE (on encaisse maintenant) OU
// - il y a une remise verrouillée (ex. trial -15%).
$op = $pr->operation ?? $operation;
$needlockedpayment = $uselock && (
    $op === Operation::UPGRADE_NOW_REPLACE_CHAIN
    || $op === Operation::QUEUE_FUTURE
    || ((int)($pr->locked_discount_percent ?? 0) > 0)
);

if ($needlockedpayment) {
    $options['mode'] = 'payment';            // override du mode
    $options['use_locked_amount'] = 1;       // signal au gateway
}


// ──────────────────────────────────────────────────────────────────────────
// 4) Appel gateway & redirection
// ──────────────────────────────────────────────────────────────────────────
$gateway = PaymentGatewayFactory::for($provider);

try {

    // === amount_minor sûr avant appel gateway ===========================
    $major        = (float)($pr->locked_final_price ?? $pr->price);
    $safeCurrency = strtoupper($pr->currency ?? $currency ?? (($provider === Provider::ALFA) ? 'RUB' : 'EUR'));

    $options['amount_minor'] = local_subs_money_to_minor_units(
        number_format($major, 2, '.', ''),
        $safeCurrency
    );
    // ====================================================================


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

    // Valider l’URL externe retournée par la passerelle.
    //
    // On accepte uniquement une URL HTTPS absolue avec un nom d’hôte.
    // Cette validation s’applique aussi bien à Stripe qu’à Alfa.
    $url = UrlFactory::validate_external_payment_url(
        (string)$url
    );

    // Si on est en mode popup (embedded=1) et provider = STRIPE,
    // on sort de l'iframe pour afficher Stripe Checkout au niveau top.
    if (
        $embedded &&
        $provider === Provider::STRIPE
    ) {
        while (ob_get_level()) {
            @ob_end_clean();
        }

        \core\session\manager::write_close();

        echo '<!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <script>
                    try {
                        if (
                            window.top &&
                            window.top !== window
                        ) {
                            window.top.location.href = ' .
                                json_encode($url) .
                            ';
                        } else {
                            window.location.href = ' .
                                json_encode($url) .
                            ';
                        }
                    } catch (e) {
                        window.location.href = ' .
                            json_encode($url) .
                        ';
                    }
                </script>
            </head>
            <body></body>
            </html>';

        exit;
    }

    // Cas normal : Alfa ou Stripe en plein écran.
    redirect($url);

} catch (\Throwable $e) {
    $reference =
        PaymentFailureReporter::
            generate_reference();

    $technicalmessage =
        PaymentFailureReporter::
            technical_message(
                $e,
                $reference,
                'subscription_checkout_create'
            );

    PaymentFailureReporter::log(
        $e,
        $reference,
        'subscription_checkout_create',
        [
            'payment_request_id' =>
                (int)$pr->id,

            'provider' =>
                (string)$provider,

            'currency' =>
                (string)(
                    $pr->currency ??
                    $currency
                ),

            'operation' =>
                (string)(
                    $pr->operation ??
                    $operation
                ),
        ]
    );

    $DB->update_record(
        'subscription_payment_request',
        (object)[
            'id' =>
                (int)$pr->id,

            'last_error' =>
                $technicalmessage,

            'status' =>
                Status::FAILED,

            'last_update' =>
                time(),
        ]
    );

    redirect(
        UrlFactory::payment_error(
            [
                'code' =>
                    'session_create',

                'reference' =>
                    $reference,
            ]
        )
    );
}