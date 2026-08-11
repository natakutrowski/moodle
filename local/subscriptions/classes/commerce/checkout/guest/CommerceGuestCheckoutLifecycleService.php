<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Applies resilient Guest Checkout lifecycle transitions. */
final class CommerceGuestCheckoutLifecycleService {
    public function __construct(private readonly CommerceGuestCheckoutSessionRepository $sessions) {}

    public function mark_payment_failed(string $purchasereference, string $reason = 'payment_failed'): ?CommerceGuestCheckoutSession {
        $session = $this->sessions->find_by_purchase_reference($purchasereference);
        if ($session === null || in_array($session->get_status(), ['active', 'purged'], true)) {
            return $session;
        }

        return $this->sessions->transition($session, 'payment_failed', [
            'expiresat' => time() + CommerceGuestCheckoutService::PAYMENT_FAILURE_TTL,
            'metadatajson' => array_replace($session->get_metadata(), [
                'payment_failure_reason' => trim($reason),
                'payment_failed_at' => time(),
            ]),
        ]);
    }

    public function mark_checkout_expired(string $purchasereference): ?CommerceGuestCheckoutSession {
        $session = $this->sessions->find_by_purchase_reference($purchasereference);
        if ($session === null || in_array($session->get_status(), ['active', 'purged'], true)) {
            return $session;
        }

        return $this->sessions->transition($session, 'checkout_expired', [
            'expiresat' => time() + CommerceGuestCheckoutService::PAYMENT_FAILURE_TTL,
            'metadatajson' => array_replace($session->get_metadata(), [
                'provider_checkout_expired_at' => time(),
            ]),
        ]);
    }

    public function expire_abandoned(CommerceGuestCheckoutSession $session): CommerceGuestCheckoutSession {
        if (in_array($session->get_status(), ['active', 'purged', 'expired'], true)) {
            return $session;
        }

        return $this->sessions->transition($session, 'expired', [
            'metadatajson' => array_replace($session->get_metadata(), [
                'expired_at' => time(),
                'expired_from_status' => $session->get_status(),
            ]),
        ]);
    }
}
