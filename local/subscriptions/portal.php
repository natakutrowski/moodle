<?php

require_once(__DIR__ . '/../../config.php');
require_login();

use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\ProviderSelector;
use local_subscriptions\payment\PortalGatewayInterface;

\local_subscriptions\subscription_config::guard_public_access();


$subid   = optional_param('subid', 0, PARAM_INT);
$return  = optional_param('return', '', PARAM_LOCALURL); // optionnel: forcer un return url

global $DB, $USER;

// 1) Résoudre la souscription cible
$sub = null;
if ($subid) {
    $sub = $DB->get_record('user_subscription', ['id'=>$subid, 'userid'=>$USER->id], '*', IGNORE_MISSING);
}
if (!$sub) {
    // fallback: dernière active (ou la plus récente) pour cet utilisateur
    $sub = $DB->get_record_sql("
        SELECT *
          FROM {user_subscription}
         WHERE userid = :u
      ORDER BY (status='active') DESC, end_date DESC, id DESC
         LIMIT 1
    ", ['u'=>$USER->id]);
}
if (!$sub) {
    // rien à gérer -> profil
    redirect(UrlFactory::my_subscriptions());
}

// 2) Trouver le provider
$provider = '';
if (!empty($sub->payment_provider)) {
    $provider = strtolower($sub->payment_provider);
} else {
    // fallback par la devise ou le plan (simple: EUR -> stripe; RUB -> alfa)
    $plan = $DB->get_record('subscription_plan', ['id'=>$sub->planid], '*', MUST_EXIST);
    $cur  = $sub->currency ?: $DB->get_field('subscription_payment_request','currency',['subscriptionid'=>$sub->id]) ?: 'EUR';
    $provider = ProviderSelector::chooseForPlan(null, $cur, null);
}

// 3) Construire le contexte pour le gateway
$ctx = [
    'user_id'              => (int)$USER->id,
    'subscription_id'      => $sub->provider_subscription_id ?? null, // ex: sub_xxx (Stripe)
    'provider_customer_id' => $sub->provider_customer_id     ?? null, // ex: cus_xxx (Stripe)
    'return_url'           => $return ?: UrlFactory::my_subscriptions()->out(false),
];

// 4) Récupérer le gateway & ses capacités
$gw = PaymentGatewayFactory::for($provider);
$supportsPortal = false;
if (method_exists($gw, 'get_capabilities')) {
    try {
        $caps = $gw->get_capabilities(); // instance de ProviderCapabilities
        if (is_object($caps) && property_exists($caps, 'supportsPortal')) {
            $supportsPortal = (bool)$caps->supportsPortal;
        }
    } catch (\Throwable $e) {
        // ignore, on tombe sur method_exists plus bas
    }
}

// 5) Si le gateway expose create_portal_session(), on l'utilise
$portalUrl = '';

if ($gw instanceof PortalGatewayInterface) {
    try {
        // $ctx contient: provider_customer_id, subscription_id (si utile), return_url
        $res = $gw->create_portal_session($ctx);

        // Compat: DTO avec getUrl() OU array['url']
        $portalUrl = is_object($res) && method_exists($res, 'getUrl')
            ? $res->getUrl()
            : (is_array($res) ? ($res['url'] ?? '') : '');
    } catch (\Throwable $ex) {
        debugging('[portal] create_portal_session error: '.$ex->getMessage(), DEBUG_DEVELOPER);
    }
}

// 6) Rediriger vers le portail si on a une URL
if (!empty($portalUrl)) {
    redirect(new \moodle_url($portalUrl));
}

// 7) Fallback : pas de portail -> afficher une page locale "Gérer mon paiement"
$PAGE->set_url(UrlFactory::portal(['subid'=>$subid]));
$PAGE->set_title(get_string('manage_billing', 'local_subscriptions'));
$PAGE->set_heading(get_string('manage_billing', 'local_subscriptions'));
echo $OUTPUT->header();

echo $OUTPUT->notification(get_string('provider_portal_not_supported', 'local_subscriptions'), 'info');
echo html_writer::tag('p', get_string('provider_portal_not_supported_desc', 'local_subscriptions', format_string($provider)));

$profile = UrlFactory::my_subscriptions()->out(false);
echo html_writer::div(
    html_writer::link($profile, get_string('view_my_subscriptions', 'local_subscriptions'), ['class'=>'btn btn-primary']),
    'mt-3'
);

echo $OUTPUT->footer();
