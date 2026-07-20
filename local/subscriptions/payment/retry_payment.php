<?php

define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__.'/../../../config.php');

use local_subscriptions\constants\Status;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\PaymentFailureReporter;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\PaymentReturn;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $CFG;

// Page (pas d'output, on redirige)
$pid   = required_param('pid', PARAM_INT);
$token = required_param('t', PARAM_ALPHANUMEXT);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(UrlFactory::retry(['pid'=>$pid,'t'=>$token]));
$PAGE->set_pagelayout('base');

// Charge la PR
$pr = $DB->get_record('subscription_payment_request', ['id'=>$pid], '*', MUST_EXIST);

// 1) Valide statut & token
if (!in_array($pr->status, [Status::PENDING, Status::FAILED, Status::CANCELED, Status::EXPIRED, Status::ERROR], true)) {
    redirect(UrlFactory::subscribe(), get_string('retry_invalid_status', 'local_subscriptions'), 5, \core\output\notification::NOTIFY_ERROR);
}
if (empty($pr->retry_token) || $pr->retry_token !== $token || ($pr->retry_expires && time() > (int)$pr->retry_expires)) {
    redirect(UrlFactory::subscribe(), get_string('retry_link_expired', 'local_subscriptions'), 5, \core\output\notification::NOTIFY_ERROR);
}

// 2) Provider de la PR
$provider = strtolower($pr->payment_provider ?: '');
if ($provider === '') {
    // Par sécurité, on peut tomber sur Stripe par défaut
    $provider = Provider::defaultProvider();
}

// 3) (re)génère un login token si expiré (pour l'auto-login sur success)
if (empty($pr->login_token) || empty($pr->login_token_expires) || (int)$pr->login_token_expires < time()) {
    $tokenLogin = bin2hex(random_bytes(32));
    $expires    = time() + 30*60;
    $DB->update_record('subscription_payment_request', (object)[
        'id'                   => $pr->id,
        'login_token'          => $tokenLogin,
        'login_token_expires'  => $expires,
        'last_update'          => time(),
    ]);
    $pr->login_token         = $tokenLogin;
    $pr->login_token_expires = $expires;
}

// 4) Options communes pour la gateway
// (On passe toujours par le routeur générique /payment/return.php)
$options = [
    'email'      => $pr->email ?: null,
    'firstname'  => $pr->firstname ?: null,
    'lastname'   => $pr->lastname ?: null,
    'product_name' => format_string($DB->get_field('subscription_plan', 'name', ['id'=>$pr->planid]) ?? ('Plan '.$pr->planid), true, ['context'=>\context_system::instance()]),
];

// URLs de retour par provider
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
        // Si tes plans gèrent du récurrent, laisse StripeGateway décider en lisant $options ou ton plan.
    ];

    // Facultatif: si abonnement récurrent, passer le price_id Stripe du plan/devise
    $plan = $DB->get_record('subscription_plan', ['id'=>$pr->planid], '*', IGNORE_MISSING);
    if (!empty($plan) && !empty($plan->is_recurring)) {
        $priceobj = $DB->get_record('subscription_plan_price',
            ['planid' => $pr->planid, 'currency' => $pr->currency], '*', IGNORE_MISSING);
        if (!empty($priceobj) && !empty($priceobj->stripe_price_id)) {
            $options['price_map'] = ['stripe_price_id' => $priceobj->stripe_price_id];
        }
    }
}

// 5) MàJ tentatives
$DB->update_record(
    'subscription_payment_request',
    (object)[
        'id' =>
            (int)$pr->id,

        'attempts' =>
            (int)$pr->attempts + 1,

        'last_attempt' =>
            time(),

        'status' =>
            Status::PENDING,

        'last_error' =>
            null,

        'last_update' =>
            time(),
    ]
);

// 6) Appel provider-agnostique
$gateway = PaymentGatewayFactory::for($provider);

try {
    $res = $gateway->create_checkout_session($pr, $options);

    // Mémorise la session/lien si renvoyés par le DTO
    $redirect = '';
    if (is_string($res)) {
        $redirect = $res;
    } elseif (is_array($res)) {
        $redirect = $res['redirect_url'] ?? $res['url'] ?? '';
        if (!empty($res['provider_session_id'])) {
            $DB->set_field('subscription_payment_request', 'sessionid', (string)$res['provider_session_id'], ['id'=>$pr->id]);
        }
    } elseif (is_object($res)) {
        if (property_exists($res, 'redirect_url')) {
            $redirect = $res->redirect_url;
        } elseif (method_exists($res, 'getUrl')) {
            $redirect = $res->getUrl();
        } elseif (method_exists($res, 'get_redirect_url')) {
            $redirect = $res->get_redirect_url();
        }
        if (property_exists($res, 'provider_session_id') && !empty($res->provider_session_id)) {
            $DB->set_field('subscription_payment_request', 'sessionid', (string)$res->provider_session_id, ['id'=>$pr->id]);
        }
    }

    $redirect =
        UrlFactory::validate_external_payment_url(
            (string)$redirect
        );

    // Enregistre le lien de paiement
    $DB->set_field('subscription_payment_request', 'payment_link', $redirect, ['id'=>$pr->id]);

    redirect($redirect);

} catch (\Throwable $e) {
    $reference =
        PaymentFailureReporter::generate_reference();

    $technicalmessage =
        PaymentFailureReporter::technical_message(
            $e,
            $reference,
            'subscription_payment_retry'
        );

    PaymentFailureReporter::log(
        $e,
        $reference,
        'subscription_payment_retry',
        [
            'payment_request_id' =>
                (int)$pr->id,

            'provider' =>
                (string)$provider,

            'currency' =>
                (string)$pr->currency,

            'attempts' =>
                (int)$pr->attempts + 1,
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
                'pid' =>
                    (int)$pr->id,

                'code' =>
                    'payment_retry',

                'reference' =>
                    $reference,
            ]
        )
    );
}