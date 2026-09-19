<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\currency\selection;

defined('MOODLE_INTERNAL') || die();

/**
 * Pure input contract for Commerce currency selection.
 *
 * H13 surfaces will progressively populate these candidates from their own
 * session/cart/checkout state. Keeping that extraction outside the resolver
 * prevents Boutique, Showroom, Cart and Checkout from growing separate rules.
 */
final class CommerceCurrencySelectionContext {
    /**
     * @param string[] $available
     */
    public function __construct(
        private readonly array $available,
        private readonly string $explicit = '',
        private readonly string $activecart = '',
        private readonly string $activeguestcheckout = '',
        private readonly string $userpreference = '',
        private readonly string $sessionpreference = '',
        private readonly string $marketdefault = '',
        private readonly string $commercedefault = 'EUR'
    ) {
    }

    /** @return string[] */
    public function get_available(): array {
        return $this->available;
    }

    public function get_explicit(): string {
        return $this->explicit;
    }

    public function get_active_cart(): string {
        return $this->activecart;
    }

    public function get_active_guest_checkout(): string {
        return $this->activeguestcheckout;
    }

    public function get_user_preference(): string {
        return $this->userpreference;
    }

    public function get_session_preference(): string {
        return $this->sessionpreference;
    }

    public function get_market_default(): string {
        return $this->marketdefault;
    }

    public function get_commerce_default(): string {
        return $this->commercedefault;
    }
}
