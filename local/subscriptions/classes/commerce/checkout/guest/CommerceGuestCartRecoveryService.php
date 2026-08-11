<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/** Restores the durable Guest Checkout cart in the current Moodle session. */
final class CommerceGuestCartRecoveryService {
    public function __construct(
        private readonly CommerceGuestCheckoutSessionRepository $sessions,
        private readonly CommerceGuestCartTransferService $carts
    ) {}

    public static function create(): self {
        global $DB;

        return new self(
            new CommerceGuestCheckoutSessionRepository($DB),
            CommerceGuestCartTransferService::create()
        );
    }

    /**
     * Restores or merges the durable Guest Checkout cart for the resolved user.
     *
     * The operation is idempotent and deliberately runs in the same request as
     * checkout preparation, so no redirect/session-write boundary can lose it.
     */
    public function recover_current(int $userid, string $currency): ?CommerceCart {
        global $SESSION;

        if ($userid <= 0) {
            return null;
        }

        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        if ($token === '') {
            return null;
        }

        $session = $this->sessions->find_by_token($token);
        $currency = strtoupper(trim($currency));

        if ($session === null
                || $session->is_expired()
                || $session->get_currency() !== $currency
                || $session->get_user_id() !== $userid
                || !in_array($session->get_status(), [
                    'existing_account',
                    'provisional',
                    'active',
                    'payment_pending',
                ], true)) {
            return null;
        }

        $durablecart = $session->get_metadata()['guest_cart_snapshot'] ?? null;
        if (!is_array($durablecart)) {
            return null;
        }

        $cart = $this->carts->transfer($userid, $currency, $durablecart);
        if ($cart === null || $cart->is_empty()) {
            throw new \RuntimeException('The durable Guest Checkout cart could not be recovered.');
        }

        $metadata = array_replace($session->get_metadata(), [
            'cart_transferred' => true,
            'cart_uuid' => $cart->get_uuid(),
            'cart_item_count' => count($cart->get_items()),
            'cart_recovered_at' => time(),
        ]);

        $this->sessions->transition($session, $session->get_status(), [
            'metadatajson' => $metadata,
        ]);

        return $cart;
    }
}
