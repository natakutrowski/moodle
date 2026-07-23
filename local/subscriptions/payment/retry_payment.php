<?php

define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__.'/../../../config.php');

use local_subscriptions\constants\Status;
use local_subscriptions\payment\PaymentFailureReporter;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\PaymentReturn;
use local_subscriptions\commerce\checkout\CommerceCheckoutFactory;
use local_subscriptions\commerce\checkout\CommerceCheckoutPersistenceService;

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

// Reconstitue explicitement le contexte nécessaire au bridge Commerce.
$options['operation'] = $pr->operation ?? null;
$options['ref_sub_id'] = !empty($pr->reference_subscription_id)
    ? (int)$pr->reference_subscription_id
    : null;
$options['amount_minor'] = (int)($pr->amount_minor ?? round((float)($pr->locked_final_price ?? $pr->price) * 100));
$options['use_locked_amount'] = 1;

if ($provider === Provider::STRIPE) {
    $plan = $plan ?? $DB->get_record('subscription_plan', ['id' => $pr->planid], '*', IGNORE_MISSING);
    $mode = !empty($plan->is_recurring) ? 'subscription' : 'payment';
    if (
        in_array((string)($pr->operation ?? ''), [
            \local_subscriptions\constants\Operation::UPGRADE_NOW_REPLACE_CHAIN,
            \local_subscriptions\constants\Operation::QUEUE_FUTURE,
        ], true)
        || (int)($pr->locked_discount_percent ?? 0) > 0
    ) {
        $mode = 'payment';
    }
    $options['mode'] = $mode;
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

// 6) Appel via la façade checkout commune
try {
    $checkoutresult = CommerceCheckoutFactory::create()->initialize(
        $pr,
        'subscription_payment_request',
        $options,
        'subscription_retry_payment',
        !empty(get_config('local_subscriptions', 'live_mode'))
    );

    $redirect = UrlFactory::validate_external_payment_url(
        $checkoutresult->get_redirect_url()
    );

    (new CommerceCheckoutPersistenceService())->persist(
        'subscription_payment_request',
        (int)$pr->id,
        $checkoutresult
    );

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