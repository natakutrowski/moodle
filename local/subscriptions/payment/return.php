<?php

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(__DIR__, 3) . '/config.php');

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\returnflow\CommercePaymentReturnResolver;
use local_subscriptions\payment\EventRouter;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;

$paymentid = required_param('paymentid', PARAM_INT);
$purchaseuuid = optional_param('purchaseuuid', '', PARAM_ALPHANUMEXT);
$result = optional_param('result', 'success', PARAM_ALPHA);
$provider = strtolower(optional_param('provider', '', PARAM_ALPHANUMEXT));
$orderid = optional_param('orderId', '', PARAM_RAW_TRIMMED);
$sessionid = optional_param('session_id', '', PARAM_RAW_TRIMMED);
$embedded = optional_param('embedded', 0, PARAM_BOOL);
$targetlang = strtolower(substr(optional_param('uilang', '', PARAM_ALPHANUMEXT), 0, 2));

function local_subscriptions_redirect_from_return(moodle_url $target, bool $embedded): void {
    $url = $target->out(false);
    while (ob_get_level()) {
        @ob_end_clean();
    }
    \core\session\manager::write_close();

    if ($embedded) {
        echo '<!doctype html><html><head><meta charset="utf-8"><script>'
            . 'try{window.top.location.href=' . json_encode($url) . ';}'
            . 'catch(e){window.location.href=' . json_encode($url) . ';}'
            . '</script></head><body></body></html>';
        exit;
    }

    redirect($target);
}

global $DB, $CFG;

$payments = new CommercePaymentRepository($DB);
$resolver = new CommercePaymentReturnResolver($DB, $payments);
$context = $resolver->resolve(
    $paymentid,
    $purchaseuuid,
    $provider !== '' ? $provider : null,
    $sessionid !== '' ? $sessionid : ($orderid !== '' ? $orderid : null)
);
$payment = $context->get_payment();
$purchase = $context->get_purchase();
$provider = $payment->get_provider();

if ($targetlang === '' && !empty($purchase->userid)) {
    $userlang = $DB->get_field('user', 'lang', ['id' => (int)$purchase->userid], IGNORE_MISSING);
    $targetlang = strtolower(substr((string)$userlang, 0, 2));
}
if (!in_array($targetlang, ['fr', 'en', 'ru'], true)) {
    $targetlang = strtolower(substr((string)($CFG->lang ?? 'ru'), 0, 2));
}
if (!in_array($targetlang, ['fr', 'en', 'ru'], true)) {
    $targetlang = 'ru';
}

$baseparams = [
    'paymentid' => $paymentid,
    'purchaseuuid' => $payment->get_purchase_uuid(),
    'reference' => (string)$purchase->reference,
    'lang' => $targetlang,
];

if ($result === 'cancel') {
    $payments->update_status($paymentid, CommercePaymentAttemptStatus::CANCELLED);
    local_subscriptions_redirect_from_return(
        UrlFactory::order_result(['result' => 'cancel'] + $baseparams),
        (bool)$embedded
    );
}

if ($provider === Provider::ALFA) {
    $providerorderid = $orderid !== ''
        ? $orderid
        : ($payment->get_provider_order_id() ?? $payment->get_provider_reference() ?? '');

    try {
        $event = PaymentGatewayFactory::for(Provider::ALFA)->parse_webhook(
            json_encode(['orderId' => $providerorderid], JSON_UNESCAPED_UNICODE),
            []
        );
        $event->meta['provider'] = Provider::ALFA;
        $event->meta['commerce_payment_id'] = (string)$paymentid;
        $event->meta['commerce_purchase_uuid'] = $payment->get_purchase_uuid();
        EventRouter::handle($event);
    } catch (\Throwable $exception) {
        $payments->update_status(
            $paymentid,
            CommercePaymentAttemptStatus::FAILED,
            null,
            ['return_error' => $exception->getMessage(), 'provider' => Provider::ALFA]
        );
        local_subscriptions_redirect_from_return(
            UrlFactory::order_result(['result' => 'failure', 'code' => 'alfa_return'] + $baseparams),
            (bool)$embedded
        );
    }

    if ($event->type !== 'checkout_completed') {
        local_subscriptions_redirect_from_return(
            UrlFactory::order_result(['result' => 'failure', 'code' => 'status'] + $baseparams),
            (bool)$embedded
        );
    }
}

/* Stripe fulfillment remains webhook-authoritative. The browser return only
 * resolves the Native payment identity; it never marks an unpaid session paid.
 */
local_subscriptions_redirect_from_return(
    UrlFactory::order_result(['result' => 'success'] + $baseparams),
    (bool)$embedded
);
