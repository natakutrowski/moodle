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

$email = required_param('email', PARAM_RAW_TRIMMED);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);

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

$reservedemail = \core_text::strtolower(trim((string)(
    $session->get_metadata()['personal_offer_reserved_email']
    ?? ''
)));
if (
    $reservedemail !== ''
    && !hash_equals(
        $reservedemail,
        \core_text::strtolower(trim($email))
    )
) {
    // Fail closed without disclosing anything about account existence.
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'code' => 'invalid_identity',
    ]);
    exit;
}

try {
    $result = CommerceGuestIdentityOtpChallengeService::create()->issue(
        $session,
        $email,
        $firstname,
        $lastname,
        current_language()
    );

    if ($result['status'] === 'delivery_failed') {
        http_response_code(503);
    }

    echo json_encode([
        'ok' => in_array($result['status'], ['sent', 'wait'], true),
        'code' => $result['status'],
        'retryAfter' => (int)$result['retryafter'],
        'expiresIn' => (int)$result['expiresin'],
    ]);
} catch (\moodle_exception $exception) {
    // Syntax/identity-field validation only. Never disclose account existence.
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'code' => 'invalid_identity',
    ]);
} catch (\Throwable $exception) {
    debugging(
        'Guest identity OTP issue failed: ' . $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'code' => 'temporarily_unavailable',
    ]);
}
