<?php

declare(strict_types=1);

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityVerificationState;

require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$token = trim(
    (string)(
        $SESSION->local_subscriptions_guest_checkout_token
        ?? ''
    )
);
$password = required_param('password', PARAM_RAW);

$repository =
    new CommerceGuestCheckoutSessionRepository($DB);
$guestsession =
    $token !== ''
        ? $repository->find_by_token($token)
        : null;

if (
    $guestsession === null
    || $guestsession->is_expired()
    || $guestsession->get_status() !== 'existing_account'
    || $guestsession->get_user_id() === null
    || !CommerceGuestIdentityVerificationState::from_session(
        $guestsession
    )->is_locked()
) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'code' => 'session_unavailable',
    ]);
    exit;
}

$expected = $DB->get_record(
    'user',
    [
        'id' => (int)$guestsession->get_user_id(),
        'deleted' => 0,
    ],
    'id,username,email',
    IGNORE_MISSING
);

if ($expected === false) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'code' => 'session_unavailable',
    ]);
    exit;
}

// The account identity comes exclusively from the verified Guest Checkout
// session. The browser never chooses which Moodle username is authenticated.
$errorcode = 0;
$user = authenticate_user_login(
    (string)$expected->username,
    $password,
    false,
    $errorcode
);

if (
    $user === false
    || (int)$user->id !== (int)$expected->id
) {
    // Deliberately generic: do not disclose authentication/account details.
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'code' => 'invalid_credentials',
    ]);
    exit;
}

complete_user_login($user);

$resumeurl = new moodle_url(
    '/local/subscriptions/guest_checkout_resume.php',
    [
        'currency' => $guestsession->get_currency(),
        'from' => 'verified_identity_login',
    ]
);

echo json_encode([
    'ok' => true,
    'code' => 'authenticated',
    'redirect' => $resumeurl->out(false),
]);
