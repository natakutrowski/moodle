<?php

declare(strict_types=1);

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityVerificationState;
use local_subscriptions\commerce\checkout\guest\CommerceGuestPaymentGate;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferFactory;

require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$token = trim((string)(
    $SESSION->local_subscriptions_guest_checkout_token
    ?? ''
));
$offertoken = trim((string)(
    $SESSION->local_subscriptions_personal_offer_token
    ?? ''
));

$repository = new CommerceGuestCheckoutSessionRepository($DB);
$session = $token !== ''
    ? $repository->find_by_token($token)
    : null;

if (
    $session === null
    || $session->is_expired()
    || $offertoken === ''
) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'code' => 'session_unavailable']);
    exit;
}

$validation = CommercePersonalOfferFactory::create($DB)
    ->validate_token($offertoken);

if (
    !$validation->is_valid()
    || $validation->get_offer() === null
) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'code' => 'offer_unavailable']);
    exit;
}

$offer = $validation->get_offer();
$reservedemail = \core_text::strtolower(
    trim((string)$offer->get_beneficiary_email())
);
$metadataemail = \core_text::strtolower(
    trim((string)(
        $session->get_metadata()['personal_offer_reserved_email']
        ?? ''
    ))
);

if (
    $reservedemail === ''
    || $metadataemail === ''
    || !hash_equals($reservedemail, $metadataemail)
    || !hash_equals(
        $offer->get_offer_uuid(),
        (string)(
            $session->get_metadata()['personal_offer_uuid']
            ?? ''
        )
    )
) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'code' => 'identity_unavailable']);
    exit;
}

$firstname = optional_param(
    'firstname',
    (string)$session->get_first_name(),
    PARAM_TEXT
);
$lastname = optional_param(
    'lastname',
    (string)$session->get_last_name(),
    PARAM_TEXT
);

try {
    $resolved = CommerceGuestCheckoutService::create()->identify(
        $session,
        $reservedemail,
        $firstname,
        $lastname,
        true
    );

    $metadata = CommerceGuestIdentityVerificationState::locked_metadata(
        array_replace(
            $resolved->get_metadata(),
            [
                'identity_proof' => 'personal_offer_signed_link',
                'personal_offer_mailbox_proof_at' => time(),
            ]
        ),
        $reservedemail,
        time()
    );
    $resolved = $repository->transition(
        $resolved,
        $resolved->get_status(),
        ['metadatajson' => $metadata]
    );

    echo json_encode([
        'ok' => true,
        'code' => 'verified_by_offer_link',
        'requiresLogin' => $resolved->get_status() === 'existing_account',
        'paymentReady' => CommerceGuestPaymentGate::is_ready($resolved),
    ]);
} catch (\moodle_exception $exception) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'code' => 'invalid_identity',
    ]);
} catch (\Throwable $exception) {
    debugging(
        'Personal Offer bearer identity confirmation failed: '
        . $exception->getMessage(),
        DEBUG_DEVELOPER
    );
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'code' => 'temporarily_unavailable',
    ]);
}
