<?php
// local/subscriptions/payment/create_session.php
require_once(dirname(__DIR__, 3) . '/config.php');

use local_subscriptions\payment\Provider;
use local_subscriptions\payment\ProviderSelector;
use local_subscriptions\payment\PaymentGatewayFactory;

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

// Appelle le gateway (nom de méthode suivant ton interface)
if (method_exists($gw, 'initCheckout')) {
    $res = $gw->initCheckout($ctx);
} elseif (method_exists($gw, 'createCheckout')) {
    $res = $gw->createCheckout($ctx);
}

// Redirige
$url = is_array($res) ? ($res['url'] ?? '') : ((method_exists($res, 'getUrl') ? $res->getUrl() : ''));
if ($url) {
    redirect(new \moodle_url($url));
}
