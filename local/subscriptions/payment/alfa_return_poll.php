<?php

declare(strict_types=1);

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once dirname(__DIR__, 3) . '/config.php';

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaReturnPollingService;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\payment\Provider;

global $DB, $SESSION, $USER;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error']);
    exit;
}

require_sesskey();

$paymentid = required_param('paymentid', PARAM_INT);
$reference = required_param('reference', PARAM_ALPHANUMEXT);

header('Content-Type: application/json; charset=utf-8');

try {
    // Reuse exactly the same ownership rules as the customer order page.
    $orders = CommerceOrderPresentationService::create();

    if (isloggedin() && !isguestuser()) {
        $order = $orders->find_for_user($reference, (int)$USER->id);
    } else {
        $guestsessions = new CommerceGuestCheckoutSessionRepository($DB);
        $guestsession = $guestsessions->find_by_purchase_reference($reference);
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));

        if (
            $guestsession === null
            || $token === ''
            || !hash_equals($guestsession->get_token(), $token)
        ) {
            throw new CommerceOrderPresentationAccessDeniedException(
                'Guest Checkout session does not own this order.'
            );
        }

        $order = $orders->find_for_user(
            $reference,
            (int)$guestsession->get_user_id()
        );
    }

    if ($order === null) {
        throw new \moodle_exception(
            'commerce_i2_order_not_found',
            'local_subscriptions'
        );
    }

    // The payment id is browser-visible but cannot be used to inspect another
    // order: bind it to the purchase UUID and Alfa provider server-side.
    $payments = new CommercePaymentRepository($DB);
    $payment = $payments->find($paymentid);

    if (
        $payment === null
        || !hash_equals($order->uuid, $payment->get_purchase_uuid())
        || $payment->get_provider() !== Provider::ALFA
    ) {
        throw new CommerceOrderPresentationAccessDeniedException(
            'Payment does not belong to this Alfa order.'
        );
    }

    $result = (new AlfaReturnPollingService(
        AlfaPaymentReconciliationService::create($DB)
    ))->check($paymentid);

    echo json_encode([
        'status' => $result->status,
        'paymentstatus' => $result->inspection->campuspaymentstatus,
        'purchasestatus' => $result->inspection->campuspurchasestatus,
    ], JSON_UNESCAPED_SLASHES);
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    http_response_code(403);
    echo json_encode(['status' => 'forbidden']);
} catch (\Throwable $exception) {
    error_log(
        '[local_subscriptions][alfa_return_poll] ' .
        json_encode([
            'payment_id' => $paymentid,
            'reference' => $reference,
            'result' => 'temporary_error',
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES)
    );

    // Keep a temporary provider/API problem recoverable by the next browser
    // poll and, ultimately, M8D/M8C.
    http_response_code(503);
    echo json_encode(['status' => 'pending']);
}
