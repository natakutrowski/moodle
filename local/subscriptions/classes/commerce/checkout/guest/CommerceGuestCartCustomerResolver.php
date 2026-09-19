<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/**
 * H12.9-A5.8.1 — resolves the authoritative Commerce cart customer.
 *
 * Before Guest identity verification the anonymous cart (customer 0) is
 * authoritative. Once a Guest Checkout owns a provisional Moodle account,
 * that provisional userid becomes the cart identity for the rest of the same
 * browser session, even though Moodle itself is not authenticated yet.
 */
final class CommerceGuestCartCustomerResolver {
    private const PROVISIONAL_STATUSES = [
        'provisional',
        'payment_pending',
        'payment_failed',
    ];

    public function __construct(
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {
    }

    public static function create(): self {
        global $DB;

        return new self(
            new CommerceGuestCheckoutSessionRepository($DB)
        );
    }

    public function resolve(string $currency): int {
        global $SESSION, $USER;

        $currency = strtoupper(trim($currency));

        if (isloggedin() && !isguestuser()) {
            return (int)$USER->id;
        }

        $token = trim((string)(
            $SESSION->local_subscriptions_guest_checkout_token
            ?? ''
        ));

        if ($token === '') {
            return 0;
        }

        $session =
            $this->sessions->find_by_token(
                $token
            );

        if (
            $session === null
            || $session->is_expired()
            || $session->get_currency() !== $currency
            || $session->get_user_id() === null
            || !in_array(
                $session->get_status(),
                self::PROVISIONAL_STATUSES,
                true
            )
        ) {
            return 0;
        }

        return (int)$session->get_user_id();
    }

    /**
     * Keep the durable Guest snapshot aligned with the authoritative
     * provisional cart after every mutation.
     */
    public function synchronize(
        int $customerid,
        string $currency,
        CommerceCart $cart,
        bool $allowcurrencyswitch = false
    ): void {
        global $SESSION;

        if (
            $customerid <= 0
            || (isloggedin() && !isguestuser())
        ) {
            return;
        }

        $token = trim((string)(
            $SESSION->local_subscriptions_guest_checkout_token
            ?? ''
        ));
        if ($token === '') {
            return;
        }

        $session =
            $this->sessions->find_by_token(
                $token
            );
        if (
            $session === null
            || $session->is_expired()
            || $session->get_user_id() !== $customerid
            || !in_array(
                $session->get_status(),
                self::PROVISIONAL_STATUSES,
                true
            )
        ) {
            return;
        }

        $currency = strtoupper(trim($currency));

        if (
            !$allowcurrencyswitch
            && $session->get_currency() !== $currency
        ) {
            return;
        }

        if (
            $cart->get_customer_id() !== $customerid
            || $cart->get_currency() !== $currency
        ) {
            throw new \coding_exception(
                'Guest provisional cart continuity received a mismatched cart.'
            );
        }

        $metadata =
            $session->get_metadata();
        $metadata['cart_transferred'] = true;
        $metadata['cart_uuid'] =
            $cart->get_uuid();
        $metadata['cart_item_count'] =
            count($cart->get_items());
        $metadata['guest_cart_snapshot'] =
            array_replace(
                $cart->to_array(),
                ['customerid' => 0]
            );
        $metadata['guest_cart_captured_at'] =
            time();
        $metadata['guest_cart_authoritative_customerid'] =
            $customerid;
        $metadata['guest_cart_continuity_synced_at'] =
            time();

        $fields = [
            'metadatajson' => $metadata,
        ];

        if (
            $allowcurrencyswitch
            && $session->get_currency() !== $currency
        ) {
            $fields['currency'] = $currency;
            $fields['purchasereference'] = null;
            $fields['paymentreference'] = null;
            $metadata['currency_switched_from'] =
                $session->get_currency();
            $metadata['currency_switched_at'] =
                time();
            $fields['metadatajson'] = $metadata;
        }

        $this->sessions->transition(
            $session,
            'provisional',
            $fields
        );
    }
}
