<?php

declare(strict_types=1);

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once dirname(__DIR__, 3) . '/config.php';

use local_subscriptions\payment\alfa\callback\AlfaCallbackService;

$payload = (string)file_get_contents('php://input');
$headers = function_exists('getallheaders') ? (array)getallheaders() : [];

header('Content-Type: text/plain; charset=utf-8');

try {
    $result = AlfaCallbackService::create()->handle(
        $payload,
        $headers,
        $_GET,
        $_POST
    );

    error_log(
        '[local_subscriptions][alfa_callback] ' .
        json_encode([
            'result' => $result['result'],
            'event_type' => $result['eventtype'],
            'order_id' => $result['identity']['orderId'] ?? null,
            'order_number' => $result['identity']['orderNumber'] ?? null,
        ], JSON_UNESCAPED_SLASHES)
    );

    http_response_code(200);
    echo 'ok';
} catch (\invalid_parameter_exception $exception) {
    error_log(
        '[local_subscriptions][alfa_callback] ' .
        json_encode([
            'result' => 'invalid_request',
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES)
    );

    http_response_code(400);
    echo 'invalid callback';
} catch (\Throwable $exception) {
    error_log(
        '[local_subscriptions][alfa_callback] ' .
        json_encode([
            'result' => 'processing_error',
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES)
    );

    // A non-2xx response allows a correctly configured provider callback
    // infrastructure to retry transient Campus/Alfa failures.
    http_response_code(500);
    echo 'processing error';
}
