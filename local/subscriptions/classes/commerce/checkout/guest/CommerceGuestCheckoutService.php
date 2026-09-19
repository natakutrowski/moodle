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

        // M9.5: the requested-currency anonymous cart is authoritative.
        // Persist that exact transferred cart as the new durable recovery
        // snapshot so a later Guest Checkout recovery cannot resurrect the
        // previous currency/price id. Durable guest snapshots deliberately use
        // customerid=0 because CommerceGuestCartTransferService validates them
        // as anonymous carts before replaying them after session regeneration.
        $metadata['guest_cart_snapshot'] = array_replace(
            $cart->to_array(),
            ['customerid' => 0]
        );
        $metadata['guest_cart_captured_at'] = time();

        return $this->sessions->transition($session, 'provisional', [
            'currency' => $currency,
            'purchasereference' => null,
            'paymentreference' => null,
            'metadatajson' => $metadata,
        ]);
    }

    /**
     * L8.2 — align a Guest Checkout session with an isolated Direct Purchase
     * currency switch without materialising or transferring the normal cart.
     *
     * @param array{
     *   currency:string,
     *   sku:string,
     *   priceid:int,
     *   quantity:int,
     *   metadata:array,
     *   cartuuid:?string,
     *   storedat?:int
     * } $directpurchase
     */
    public function switch_direct_purchase_currency(
        CommerceGuestCheckoutSession $session,
        string $currency,
        array $directpurchase
    ): CommerceGuestCheckoutSession {
        $currency = strtoupper(trim($currency));
        $allowedstatuses = [
            'identity_pending',
            'existing_account',
            'provisional',
            'payment_failed',
        ];

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \coding_exception(
                'Guest Checkout currency must use ISO 4217 format.'
            );
        }
        if (
            $session->is_expired()
            || !in_array($session->get_status(), $allowedstatuses, true)
        ) {
            throw new \coding_exception(
                'This Guest Checkout session cannot switch Direct Purchase currency.'
            );
        }

        $directcurrency = strtoupper(trim((string)($directpurchase['currency'] ?? '')));
        $cartuuid = strtolower(trim((string)($directpurchase['cartuuid'] ?? '')));
        if (
            $directcurrency !== $currency
            || trim((string)($directpurchase['sku'] ?? '')) === ''
            || (int)($directpurchase['priceid'] ?? 0) <= 0
            || (int)($directpurchase['quantity'] ?? 0) <= 0
            || !preg_match('/^[a-f0-9]{32}$/', $cartuuid)
        ) {
            throw new \coding_exception(
                'Invalid Direct Purchase Guest Checkout currency payload.'
            );
        }

        $metadata = $session->get_metadata();
        $metadata['direct_purchase'] = $directpurchase;
        $metadata['currency_switched_from'] = $session->get_currency();
        $metadata['currency_switched_at'] = time();

        // A purchase/payment attempt is immutable in its original currency.
        // Explicitly switching currency abandons only the Guest-session resume
        // pointers; the old Native purchase/payment remains in the audit ledger.
        unset(
            $metadata['resume_purchase_reference'],
            $metadata['payment_started_at'],
            $metadata['payment_failure_reason'],
            $metadata['payment_failed_at']
        );

        $status = $session->get_status();
        if ($status === 'payment_failed') {
            $status = $session->get_user_id() !== null
                ? 'provisional'
                : 'identity_pending';
        }

        return $this->sessions->transition($session, $status, [
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
        string $lastname,
        bool $allowprovisionalresume = false
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

        $identified = $this->accounts->provision(
            $session,
            $email,
            $firstname,
            $lastname,
            $allowprovisionalresume
        );
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
