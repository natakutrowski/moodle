<?php
// local/subscriptions/payment/create_session.php
require_once(dirname(__DIR__, 3) . '/config.php');

use local_subscriptions\payment\Provider;
use local_subscriptions\payment\ProviderSelector;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\url\UrlFactory;

// ---- Params communs (comme aujourd’hui) ----
$planid     = required_param('planid',    PARAM_INT);
$operation  = required_param('operation', PARAM_ALPHANUMEXT); // purchase_new|queue_future|upgrade_now_replace_chain
$currency   = required_param('currency',  PARAM_ALPHANUMEXT);
$refsubid   = optional_param('ref_subid', 0, PARAM_INT);
$extra_json = optional_param('extra_json','', PARAM_RAW);

// override de test (ex: &provider=alfa)
$providerOverride = optional_param('provider', '', PARAM_ALPHANUMEXT);

global $DB, $USER;
$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);

// ---- Choix du provider ----
$provider = $providerOverride ? strtolower($providerOverride)
                              : ProviderSelector::chooseForPlan($plan, $currency, $USER->id ?? null);
if (!$provider) $provider = Provider::STRIPE;

// ---- STRIPE : on délègue à TON script existant (logique intacte) ----
if ($provider === Provider::STRIPE) {
    // Essaie d'abord payment/stripe/create_session.php, puis /stripe/create_session.php (selon ton arbo actuelle)
    $legacy1 = __DIR__ . '/stripe/create_session.php';
    $legacy2 = dirname(__DIR__) . '/stripe/create_session.php';
    if (file_exists($legacy1)) { require($legacy1); exit; }
    if (file_exists($legacy2)) { require($legacy2); exit; }
}

// ---- AUTRE provider (ex: Alfa) via la Factory ----
$gw = PaymentGatewayFactory::for($provider);

// Construis un contexte agnostique (les clés sont simples & stables)
$ctx = [
    'operation'    => $operation,
    'user_id'      => $USER->id ?? null,
    'plan_id'      => (int)$planid,
    'currency'     => $currency,
    'ref_sub_id'   => $refsubid ?: null,
    'meta'         => $extra_json ? (json_decode($extra_json, true) ?: []) : [],
];

// Si ton formulaire envoie déjà un montant forcé en cents (upgrade), prends-le
if (isset($_POST['amount_minor'])) {
    $ctx['amount_minor'] = (int)$_POST['amount_minor'];
}

// === Ensure Payment Request ($__pr) ======================================
$__pr = null;

// 1) Essayer des variables déjà définies par l'appelant (compat anciennes implémentations)
if (isset($paymentrequest) && is_object($paymentrequest))      { $__pr = $paymentrequest; }
elseif (isset($payment_request) && is_object($payment_request)) { $__pr = $payment_request; }
elseif (isset($pr) && is_object($pr))                           { $__pr = $pr; }

// 2) Essayer via paramètre de requête ?prid=...
if (!$__pr) {
    $pridparam = optional_param('pid', 0, PARAM_INT);
    if ($pridparam) {
        $__pr = $DB->get_record('subscription_payment_request', ['id' => $pridparam], '*', MUST_EXIST);
    }
}

// 3) Créer un PR si nécessaire (provider-agnostique mais on met déjà alfa ici)
if (!$__pr) {
    // Détermination du prix en "major"
    // --- Détermination du prix "major" (RUB  => ex: 1990.00) -------------------
    $priceMajor = null;

    // 1) Si on reçoit déjà un montant forcé en minor (upgrade, etc.)
    if (!empty($ctx['amount_minor']) && is_numeric($ctx['amount_minor'])) {
        $priceMajor = round(((int)$ctx['amount_minor']) / 100, 2);

    // 2) Si un $price a été passé par l'appelant
    } elseif (isset($price) && is_numeric($price)) {
        $priceMajor = round((float)$price, 2);

    // 3) Sinon, on va chercher le prix du plan en DB (table subscription_plan_price)
    } else {
        // Résoudre planid & currency
        $planidResolved = (int)($planid ?? ($plan->id ?? 0));
        $currencyResolved = strtoupper($currency ?? '');

        if (!$planidResolved || !$currencyResolved) {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'Missing planid or currency to lookup price.'
            );
        }

        // ⚠️ Nom de table: utilise le nom SANS préfixe "mdl_"
        // Ta structure: mdl_subscription_plan_price(id, plan_id, currency, price)
        // donc ici: 'subscription_plan_price'
        $row = $DB->get_record('subscription_plan_price',
            ['planid' => $planidResolved, 'currency' => $currencyResolved],
            'price',
            IGNORE_MISSING
        );

        if ($row && isset($row->price)) {
            $priceMajor = round((float)$row->price, 2);
        } else {
            // Dernier recours : essayer une colonne générique sur la table des plans (si elle existe chez toi)
            $planrec = $DB->get_record('subscription_plan', ['id' => $planidResolved], '*', IGNORE_MISSING);
            if ($planrec) {
                if (isset($planrec->price) && is_numeric($planrec->price)) {
                    $priceMajor = round((float)$planrec->price, 2);
                } else {
                    // Variante: si tu stockes par devise (ex: price_rub)
                    $lc = strtolower($currencyResolved);
                    $field = 'price_'.$lc;
                    if (isset($planrec->$field) && is_numeric($planrec->$field)) {
                        $priceMajor = round((float)$planrec->$field, 2);
                    }
                }
            }
        }
    }

    if ($priceMajor === null) {
        throw new \moodle_exception(
            'paymentgatewayerror',
            'local_subscriptions',
            '',
            'Cannot determine price (pass amount_minor or define plan price).'
        );
    }


    $prrec = (object)[
        'planid'         => (int)($planid ?? ($plan->id ?? 0)),
        'userid'         => ($USER->id ?? null),
        'email'          => $email ?? ($USER->email ?? null),
        'firstname'      => $firstname ?? ($USER->firstname ?? ''),
        'lastname'       => $lastname ?? ($USER->lastname ?? ''),
        'currency'       => strtoupper($currency),
        'price'          => $priceMajor,
        'payment_provider' => 'alfa',     // ou '' si tu veux garder 100% agnostique
        'sessionid'      => null,         // sera rempli par Alfa (orderId)
        'status'         => Status::PENDING,
        'creation_date'  => time(),
        'operation'      => $operation ?? '', // si tu l'utilises
        'reference_subscription_id' => $refsubid ?? null,
    ];
    $prrec->id = $DB->insert_record('subscription_payment_request', $prrec);
    $__pr = $prrec;
}

// === Appel gateway (Alfa) ================================================
$gateway = $gw ?? PaymentGatewayFactory::for(Provider::ALFA);

// URLs de retour si non définies plus haut
if (empty($returnurl) || empty($failurl)) {
    $returnurl = $CFG->wwwroot . '/local/subscriptions/payment/alfa_return.php?pid=' . $__pr->id;
    $failurl   = $returnurl . '&fail=1'; // => payment_cancel
}

try {
    $res = $gateway->create_checkout_session($__pr, [
        'returnurl' => $returnurl,
        'failurl'   => $failurl,
        'language'  => 'ru',
        'email'     => $__pr->email ?? null,
    ]);
} catch (\Throwable $e) {
    // Log DB + redirection UX propre
    $DB->update_record('subscription_payment_request', (object)[
        'id'         => $__pr->id,
        'last_error' => $e->getMessage(),
        'status'     => 'error',
    ]);
    redirect(UrlFactory::payment_error([
        'code'    => 'alfa_register_error',
        'msg'     => $e->getMessage(),
    ]));
    exit;
}


// === Redirection vers la page de paiement ================================
$url = '';
if (is_string($res)) {
    $url = $res;
} elseif (is_array($res)) {
    $url = $res['redirect_url'] ?? $res['url'] ?? '';
} elseif (is_object($res)) {
    if (property_exists($res, 'redirect_url')) {
        $url = $res->redirect_url;
    } elseif (method_exists($res, 'getUrl')) {
        $url = $res->getUrl();
    } elseif (method_exists($res, 'get_redirect_url')) {
        $url = $res->get_redirect_url();
    }
}

if (empty($url)) {
    throw new \moodle_exception('paymentgatewayerror', 'local_subscriptions', '', 'Checkout init returned no redirect URL.');
}

redirect(new moodle_url($url));


