<?php

define('NO_DEBUG_DISPLAY', true);
require_once(dirname(__DIR__, 3) . '/config.php');

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\returnflow\CommercePaymentReturnResolver;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaInstantReturnReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaInstantReturnResult;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\NativeAlfaInstantReturnSleeper;
use local_subscriptions\commerce\payment\reconciliation\stripe\StripePaymentReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\stripe\returnflow\StripeReturnPollingService;
use local_subscriptions\commerce\payment\provider\paypal\PayPalReturnCaptureService;
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

$embeddedearlysplash = false;

if ($embedded) {
    // H12.5.5: take visual control immediately, before payment reconciliation.
    // The iframe may otherwise stay blank while Alfa status checks run.
    $earlytitle = get_string(
        'commerce_payment_splash_finalizing_title',
        'local_subscriptions'
    );
    $earlymessage = get_string(
        'commerce_payment_splash_finalizing_message',
        'local_subscriptions'
    );

    while (ob_get_level()) {
        @ob_end_flush();
    }

    echo '<!doctype html><html><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<style>'
        . 'html,body{margin:0;min-height:100%;font-family:Arial,sans-serif;background:#fff}'
        . '.cf-splash{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;'
        . 'padding:2rem;background:#fff;color:#212529;text-align:center}'
        . '.cf-spinner{width:2.25rem;height:2.25rem;margin:0 auto 1rem;border:.22rem solid #e9ecef;'
        . 'border-top-color:#e83e8c;border-radius:50%;animation:cfspin .75s linear infinite}'
        . '.cf-splash h1{margin:0 0 .45rem;font-size:1.25rem}'
        . '.cf-splash p{margin:0;color:#6c757d}'
        . '@keyframes cfspin{to{transform:rotate(360deg)}}'
        . '</style></head><body>'
        . '<div class="cf-splash"><div><div class="cf-spinner" aria-hidden="true"></div>'
        . '<h1>' . s($earlytitle) . '</h1>'
        . '<p>' . s($earlymessage) . '</p></div></div>'
        . '<script>(function(){'
        . 'try{if(window.top&&window.top!==window){'
        . 'var d=window.top.document;'
        . 'var old=d.getElementById("campus-payment-finalizing-overlay");'
        . 'if(!old){'
        . 'var o=d.createElement("div");'
        . 'o.id="campus-payment-finalizing-overlay";'
        . 'o.setAttribute("aria-live","polite");'
        . 'o.style.cssText="position:fixed;inset:0;z-index:2147483647;display:flex;'
        . 'align-items:center;justify-content:center;padding:2rem;background:#fff;'
        . 'color:#212529;text-align:center;font-family:inherit";'
        . 'o.innerHTML="<div><div style=\"width:2.25rem;height:2.25rem;margin:0 auto 1rem;'
        . 'border:.22rem solid #e9ecef;border-top-color:#e83e8c;border-radius:50%;'
        . 'animation:cfspin .75s linear infinite\"></div>'
        . '<h1 style=\"margin:0 0 .45rem;font-size:1.25rem\">'
        . addslashes($earlytitle)
        . '</h1><p style=\"margin:0;color:#6c757d\">'
        . addslashes($earlymessage)
        . '</p></div>";'
        . 'd.body.appendChild(o);'
        . '}'
        . '}}catch(e){}'
        . '})();</script>';

    if (function_exists('fastcgi_finish_request')) {
        // Do not finish the request: we only want the browser to receive
        // the already-generated splash while reconciliation continues.
    }

    @ob_flush();
    @flush();

    $embeddedearlysplash = true;
}

function local_subscriptions_redirect_from_return(moodle_url $target, bool $embedded): void {
    global $embeddedearlysplash;

    $url = $target->out(false);
    while (ob_get_level()) {
        @ob_end_clean();
    }
    \core\session\manager::write_close();

    if ($embedded) {
        if ($embeddedearlysplash) {
            echo '<script>(function(){var url=' . json_encode($url)
                . ';try{if(window.top&&window.top!==window){'
                . 'window.top.location.href=url;'
                . '}else{window.location.href=url;}}'
                . 'catch(e){window.location.href=url;}})();</script></body></html>';
            exit;
        }

        $title = get_string('commerce_payment_splash_finalizing_title', 'local_subscriptions');
        $message = get_string('commerce_payment_splash_finalizing_message', 'local_subscriptions');
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<style>html,body{margin:0;min-height:100%;font-family:Arial,sans-serif;background:#fff}.cf-splash{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:2rem;background:#fff;color:#212529;text-align:center}.cf-spinner{width:2.25rem;height:2.25rem;margin:0 auto 1rem;border:.22rem solid #e9ecef;border-top-color:#e83e8c;border-radius:50%;animation:cfspin .75s linear infinite}.cf-splash h1{margin:0 0 .45rem;font-size:1.25rem}.cf-splash p{margin:0;color:#6c757d}@keyframes cfspin{to{transform:rotate(360deg)}}</style></head><body>'
            . '<div class="cf-splash"><div><div class="cf-spinner" aria-hidden="true"></div><h1>' . s($title) . '</h1><p>' . s($message) . '</p></div></div>'
            . '<script>(function(){var url=' . json_encode($url) . ';var title=' . json_encode($title) . ';var message=' . json_encode($message) . ';try{if(window.top&&window.top!==window){var d=window.top.document;var o=d.createElement("div");o.style.cssText="position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;padding:2rem;background:#fff;color:#212529;text-align:center;font-family:inherit";o.innerHTML="<div><div style=\"width:2.25rem;height:2.25rem;margin:0 auto 1rem;border:.22rem solid #e9ecef;border-top-color:#e83e8c;border-radius:50%\"></div><h1 style=\"margin:0 0 .45rem;font-size:1.25rem\">"+title+"</h1><p style=\"margin:0;color:#6c757d\">"+message+"</p></div>";d.body.appendChild(o);window.setTimeout(function(){window.top.location.href=url;},50);}else{window.location.href=url;}}catch(e){window.location.href=url;}})();</script></body></html>';
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

if ($provider === Provider::PAYPAL) {
    $paypalorderid = $orderid !== '' ? $orderid : optional_param('token', '', PARAM_RAW_TRIMMED);
    try {
        (PayPalReturnCaptureService::create($DB))->capture($paymentid, $paypalorderid);
        error_log('[local_subscriptions][paypal_return_capture] ' . json_encode([
            'payment_id' => $paymentid,
            'purchase_reference' => (string)$purchase->reference,
            'paypal_order_id' => $paypalorderid,
            'result' => 'completed',
        ], JSON_UNESCAPED_SLASHES));
    } catch (\Throwable $exception) {
        error_log('[local_subscriptions][paypal_return_capture] ' . json_encode([
            'payment_id' => $paymentid,
            'purchase_reference' => (string)$purchase->reference,
            'paypal_order_id' => $paypalorderid,
            'result' => 'failed',
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES));
        local_subscriptions_redirect_from_return(
            UrlFactory::order_result(['result' => 'failure', 'code' => 'paypal_capture'] + $baseparams),
            (bool)$embedded
        );
    }
}

if ($provider === Provider::ALFA) {
    try {
        $instant = new AlfaInstantReturnReconciliationService(
            AlfaPaymentReconciliationService::create($DB),
            new NativeAlfaInstantReturnSleeper()
        );
        $instantresult = $instant->reconcile($paymentid);

        error_log(
            '[local_subscriptions][alfa_return_reconciliation] ' .
            json_encode([
                'payment_id' => $paymentid,
                'purchase_reference' => (string)$purchase->reference,
                'result' => $instantresult->status,
                'attempts' => $instantresult->attempts,
                'campus_payment_status' => $instantresult->inspection->campuspaymentstatus,
                'campus_purchase_status' => $instantresult->inspection->campuspurchasestatus,
                'alfa_order_status' => $instantresult->inspection->provider->orderstatus,
                'alfa_payment_state' => $instantresult->inspection->provider->paymentstate,
                'blockers' => $instantresult->inspection->blockers,
            ], JSON_UNESCAPED_SLASHES)
        );

        if ($instantresult->is_unsafe()) {
            local_subscriptions_redirect_from_return(
                UrlFactory::order_result([
                    'result' => 'failure',
                    'code' => 'alfa_reconciliation',
                ] + $baseparams),
                (bool)$embedded
            );
        }

        // COMPLETE redirects to an immediately usable success page.
        // PENDING deliberately also uses browser result=success: the durable
        // payment state remains pending, so CommercePostPaymentStateResolver
        // renders "confirmation in progress" while M8D/M8C keep working.
    } catch (\Throwable $exception) {
        // A temporary status/API failure after the customer has returned from
        // the bank must never downgrade the payment to FAILED. Preserve the
        // durable pending state and let callback/cron reconciliation recover it.
        error_log(
            '[local_subscriptions][alfa_return_reconciliation] ' .
            json_encode([
                'payment_id' => $paymentid,
                'purchase_reference' => (string)$purchase->reference,
                'result' => 'temporary_error',
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ], JSON_UNESCAPED_SLASHES)
        );
    }
}

if ($provider === Provider::STRIPE) {
    try {
        $striperesult = (new StripeReturnPollingService(
            StripePaymentReconciliationService::create($DB)
        ))->check($paymentid);

        error_log('[local_subscriptions][stripe_return_reconciliation] ' . json_encode([
            'payment_id' => $paymentid,
            'purchase_reference' => (string)$purchase->reference,
            'result' => $striperesult->status,
            'campus_payment_status' => $striperesult->inspection->campuspaymentstatus,
            'campus_purchase_status' => $striperesult->inspection->campuspurchasestatus,
            'stripe_checkout_status' => $striperesult->inspection->provider->checkoutstatus,
            'stripe_payment_status' => $striperesult->inspection->provider->paymentstatus,
            'stripe_profile' => $striperesult->inspection->provider->profile,
            'blockers' => $striperesult->inspection->blockers,
        ], JSON_UNESCAPED_SLASHES));

        if ($striperesult->status === 'unsafe') {
            local_subscriptions_redirect_from_return(
                UrlFactory::order_result(['result'=>'failure','code'=>'stripe_reconciliation'] + $baseparams),
                (bool)$embedded
            );
        }
    } catch (\Throwable $exception) {
        error_log('[local_subscriptions][stripe_return_reconciliation] ' . json_encode([
            'payment_id'=>$paymentid,'purchase_reference'=>(string)$purchase->reference,
            'result'=>'temporary_error','exception'=>get_class($exception),'message'=>$exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES));
    }
}

local_subscriptions_redirect_from_return(
    UrlFactory::order_result(['result' => 'success'] + $baseparams),
    (bool)$embedded
);
