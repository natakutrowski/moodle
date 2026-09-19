<?php

declare(strict_types=1);

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once dirname(__DIR__, 3)
    . '/config.php';

use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;
use local_subscriptions\commerce\payment\provider\paypal\webhook\PayPalWebhookService;
use local_subscriptions\commerce\payment\provider\paypal\webhook\PayPalWebhookSignatureVerifier;

$payload =
    (string)file_get_contents('php://input');

$headers = function_exists('getallheaders')
    ? (array)getallheaders()
    : [];

header(
    'Content-Type: text/plain; charset=utf-8'
);

try {
    $configuration =
        new PayPalGatewayConfiguration();

    $verified =
        (
            new PayPalWebhookSignatureVerifier(
                $configuration
            )
        )->verify(
            $payload,
            $headers
        );

    $result =
        PayPalWebhookService::create($DB)
            ->handle($verified);

    error_log(
        '[local_subscriptions][paypal_webhook] '
        . json_encode(
            [
                'result' => $result,
                'environment' =>
                    $configuration->get_environment(),
                'event_id' =>
                    $verified->get_event_id(),
                'event_type' =>
                    $verified->get_event_type(),
            ],
            JSON_UNESCAPED_SLASHES
        )
    );

    http_response_code(200);
    echo 'ok';
} catch (
    \UnexpectedValueException $exception
) {
    error_log(
        '[local_subscriptions][paypal_webhook] '
        . json_encode(
            [
                'result' =>
                    'invalid_signature_or_payload',
                'message' =>
                    $exception->getMessage(),
            ],
            JSON_UNESCAPED_SLASHES
        )
    );

    http_response_code(400);
    echo 'invalid webhook';
} catch (\Throwable $exception) {
    error_log(
        '[local_subscriptions][paypal_webhook] '
        . json_encode(
            [
                'result' => 'processing_error',
                'exception' =>
                    $exception::class,
                'message' =>
                    $exception->getMessage(),
            ],
            JSON_UNESCAPED_SLASHES
        )
    );

    http_response_code(500);
    echo 'processing error';
}
