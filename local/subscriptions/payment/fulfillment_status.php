<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\order\presentation\CommercePostPaymentStateResolver;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $SESSION, $USER;

require_sesskey();

$reference = required_param(
    'reference',
    PARAM_ALPHANUMEXT
);


$service =
    CommerceOrderPresentationService::create();

try {
    if (
        isloggedin()
        && !isguestuser()
    ) {
        $order =
            $service->find_for_user(
                $reference,
                (int)$USER->id
            );
    } else {
        $sessions =
            new CommerceGuestCheckoutSessionRepository(
                $DB
            );
        $token =
            trim(
                (string)(
                    $SESSION
                        ->local_subscriptions_guest_checkout_token
                    ?? ''
                )
            );
        $session =
            $token !== ''
                ? $sessions->find_by_token(
                    $token
                )
                : null;

        if (
            $session === null
            || $session->is_expired()
            || $session->get_user_id() === null
        ) {
            throw new CommerceOrderPresentationAccessDeniedException(
                'Guest Checkout session cannot own this order.'
            );
        }

        $sessionmetadata =
            $session->get_metadata();
        $order =
            $service->find_for_guest_session(
                $reference,
                (string)($session->get_purchase_reference() ?? ''),
                (string)($sessionmetadata['resume_purchase_reference'] ?? '')
            );
    }
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    http_response_code(403);
    header(
        'Content-Type: application/json; charset=utf-8'
    );
    echo json_encode([
        'status' => 'unsafe',
    ]);
    exit;
}

if ($order === null) {
    http_response_code(404);
    header(
        'Content-Type: application/json; charset=utf-8'
    );
    echo json_encode([
        'status' => 'missing',
    ]);
    exit;
}

$state =
    (new CommercePostPaymentStateResolver())
        ->resolve(
            $order,
            ''
        );

$status = match ($state->code) {
    'success' => 'ready',
    'processing', 'pending' => 'pending',
    'failed', 'cancelled' => 'failed',
    default => 'pending',
};

header(
    'Content-Type: application/json; charset=utf-8'
);

echo json_encode(
    [
        'status' => $status,
        'state' => $state->code,
        'paid' => $order->is_paid(),
        'accessesReady' =>
            $order->has_available_accesses(),    ],
    JSON_UNESCAPED_SLASHES
);
