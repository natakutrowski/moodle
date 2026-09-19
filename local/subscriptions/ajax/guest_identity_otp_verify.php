<?php

declare(strict_types=1);

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityOtpChallengeService;

require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$token = trim(
    (string)(
        $SESSION->local_subscriptions_guest_checkout_token
        ?? ''
    )
);
$code = required_param('code', PARAM_RAW_TRIMMED);

$repository = new CommerceGuestCheckoutSessionRepository($DB);
$session = $token !== ''
    ? $repository->find_by_token($token)
    : null;

if ($session === null || $session->is_expired()) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'code' => 'session_unavailable',
    ]);
    exit;
}

try {
    $result = CommerceGuestIdentityOtpChallengeService::create()->verify(
        $session,
        $code
    );

    $verified = $result['status'] === 'verified';

    echo json_encode([
        'ok' => $verified,
        'code' => $result['status'],
        'attemptsRemaining' => (int)$result['attemptsremaining'],
        // Account existence is disclosed only after mailbox possession was
        // proven by a correct OTP.
        'requiresLogin' => $verified
            ? (bool)$result['requireslogin']
            : false,
        'paymentReady' => $verified
            ? (bool)$result['paymentready']
            : false,
    ]);
} catch (\Throwable $exception) {
    debugging(
        'Guest identity OTP verification failed: ' . $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'code' => 'temporarily_unavailable',
        'attemptsRemaining' => 0,
        'requiresLogin' => false,
        'paymentReady' => false,
    ]);
}
