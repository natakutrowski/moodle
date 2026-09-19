<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/**
 * H12.9-A5.7.4 — authoritative Guest Checkout payment gate.
 *
 * A payment provider may only be initialised after the browser has proved
 * mailbox ownership and the Guest Checkout has resolved a usable Moodle user.
 */
final class CommerceGuestPaymentGate {
    private const READY_STATUSES = [
        'provisional',
        'payment_pending',
        'payment_failed',
        'paid_pending_activation',
        'active',
    ];

    public static function is_ready(
        ?CommerceGuestCheckoutSession $session
    ): bool {
        if (
            $session === null
            || $session->is_expired()
            || $session->get_user_id() === null
            || !in_array(
                $session->get_status(),
                self::READY_STATUSES,
                true
            )
        ) {
            return false;
        }

        return CommerceGuestIdentityVerificationState::from_session(
            $session
        )->is_locked();
    }

    private function __construct() {
    }
}
