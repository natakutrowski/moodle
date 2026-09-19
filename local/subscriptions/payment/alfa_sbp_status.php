<?php

declare(strict_types=1);

define('AJAX_SCRIPT', true);

require_once __DIR__ . '/../../../config.php';

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$paymentid =
    required_param(
        'paymentid',
        PARAM_INT
    );
$purchaseuuid =
    required_param(
        'purchaseuuid',
        PARAM_ALPHANUMEXT
    );
$expires =
    required_param(
        'expires',
        PARAM_INT
    );
$signature =
    required_param(
        'signature',
        PARAM_ALPHANUM
    );

$respond = static function (
    array $payload,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );
    exit;
};

if (
    $paymentid <= 0
    || $purchaseuuid === ''
    || $expires < time()
) {
    $respond(
        [
            'ok' => false,
            'state' => 'expired',
        ],
        403
    );
}

$secret =
    (string)(
        $CFG->passwordsaltmain
        ?? $CFG->wwwroot
    );

$expected =
    hash_hmac(
        'sha256',
        $paymentid
            . '|'
            . $purchaseuuid
            . '|'
            . $expires,
        $secret
    );

if (!hash_equals($expected, $signature)) {
    $respond(
        [
            'ok' => false,
            'state' => 'forbidden',
        ],
        403
    );
}

try {
    $service =
        AlfaPaymentReconciliationService::create(
            $DB
        );

    $inspection =
        $service->inspect_payment(
            $paymentid
        );

    if (
        $inspection->purchaseuuid
        !== $purchaseuuid
    ) {
        $respond(
            [
                'ok' => false,
                'state' => 'forbidden',
            ],
            403
        );
    }

    if ($inspection->alreadycomplete) {
        $respond([
            'ok' => true,
            'state' => 'paid',
            'complete' => true,
        ]);
    }

    if (
        $inspection->providerpaid
        && $inspection->reconcilable
    ) {
        $inspection =
            $service->reconcile_payment(
                $paymentid
            );

        $respond([
            'ok' => true,
            'state' =>
                $inspection->alreadycomplete
                    ? 'paid'
                    : 'processing',
            'complete' =>
                $inspection->alreadycomplete,
        ]);
    }

    $respond([
        'ok' => true,
        'state' => 'pending',
        'complete' => false,
        'providerOrderStatus' =>
            $inspection->provider->orderstatus,
        'providerPaymentState' =>
            $inspection->provider->paymentstate,
    ]);
} catch (\Throwable $exception) {
    debugging(
        '[local_subscriptions][sbp_status_poll] '
        . get_class($exception)
        . ': '
        . $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    $respond(
        [
            'ok' => false,
            'state' => 'error',
        ],
        500
    );
}
