<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** H5.2 application service for Guest Checkout session and identity lifecycle. */
final class CommerceGuestCheckoutService {
    public const ABANDONED_TTL = 1209600; // 14 days.
    public const PAYMENT_FAILURE_TTL = 2592000; // 30 days.

    public function __construct(
        private readonly CommerceGuestCheckoutSessionRepository $sessions,
        private readonly CommerceGuestAccountProvisioner $accounts,
        private readonly CommerceGuestCartTransferService $carts
    ) {}

    public static function create(): self {
        global $DB;
        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        return new self(
            $sessions,
            new CommerceGuestAccountProvisioner($DB, $sessions),
            CommerceGuestCartTransferService::create()
        );
    }

    public function start(string $currency, array $metadata = []): CommerceGuestCheckoutSession {
        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('Guest Checkout currency must use ISO 4217 format.');
        }
        return $this->sessions->create($currency, time() + self::ABANDONED_TTL, $metadata);
    }

    /**
     * Move an active provisional Guest Checkout session to another currency.
     *
     * The signed Personal Offer entry prepares the requested currency as the
     * anonymous cart first. We then transfer that authoritative cart to the same
     * provisional Moodle user instead of provisioning a second account/session.
     */
    public function switch_provisional_currency(
        CommerceGuestCheckoutSession $session,
        string $currency
    ): CommerceGuestCheckoutSession {
        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception('Guest Checkout currency must use ISO 4217 format.');
        }
        if ($session->is_expired()
                || $session->get_status() !== 'provisional'
                || $session->get_user_id() === null) {
            throw new \coding_exception(
                'Only an active provisional Guest Checkout session can switch currency.'
            );
        }
        if ($session->get_currency() === $currency) {
            return $session;
        }

        $cart = $this->carts->transfer($session->get_user_id(), $currency);
        if ($cart === null || $cart->is_empty()) {
            throw new \moodle_exception(
                'commerce_guest_checkout_identity_required',
                'local_subscriptions'
            );
        }

        $metadata = $session->get_metadata();
        $metadata['currency_switched_at'] = time();
        $metadata['currency_switched_from'] = $session->get_currency();
        $metadata['cart_transferred'] = true;
        $metadata['cart_uuid'] = $cart->get_uuid();
        $metadata['cart_item_count'] = count($cart->get_items());

        return $this->sessions->transition($session, 'provisional', [
            'currency' => $currency,
            'purchasereference' => null,
            'paymentreference' => null,
            'metadatajson' => $metadata,
        ]);
    }

    public function identify(
        CommerceGuestCheckoutSession $session,
        string $email,
        string $firstname,
        string $lastname
    ): CommerceGuestCheckoutSession {
        if ($session->is_expired()) {
            throw new \RuntimeException('The Guest Checkout session has expired.');
        }

        // Persist the anonymous cart before login can regenerate the Moodle session.
        $durablecart = $this->carts->capture($session->get_currency());
        if ($durablecart !== null) {
            $session = $this->sessions->transition($session, $session->get_status(), [
                'metadatajson' => array_replace($session->get_metadata(), [
                    'guest_cart_snapshot' => $durablecart,
                    'guest_cart_captured_at' => time(),
                ]),
            ]);
        }

        $identified = $this->accounts->provision($session, $email, $firstname, $lastname);
        if ($identified->get_status() === 'provisional' && $identified->get_user_id() !== null) {
            $cart = $this->carts->transfer(
                $identified->get_user_id(),
                $identified->get_currency(),
                $this->durable_cart($identified)
            );
            if ($cart !== null) {
                $identified = $this->sessions->transition($identified, 'provisional', [
                    'metadatajson' => array_replace($identified->get_metadata(), [
                        'cart_transferred' => true,
                        'cart_uuid' => $cart->get_uuid(),
                        'cart_item_count' => count($cart->get_items()),
                    ]),
                ]);
            }
        }
        return $identified;
    }

    /** @return array<string, mixed>|null */
    private function durable_cart(CommerceGuestCheckoutSession $session): ?array {
        $cart = $session->get_metadata()['guest_cart_snapshot'] ?? null;
        return is_array($cart) ? $cart : null;
    }
}
