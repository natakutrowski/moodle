<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;

/** Financially complete, provider-neutral checkout summary. */
final class CommerceCheckoutSummary {
    public function __construct(
        private readonly CommerceCartSnapshot $cart,
        private readonly CommerceCheckoutContext $context,
        private readonly CommerceCheckoutValidationResult $validation,
        private readonly int $createdat
    ) {
        if ($createdat <= 0) {
            throw new \coding_exception('Checkout summary timestamp must be positive.');
        }
    }

    public function get_cart_snapshot(): CommerceCartSnapshot { return $this->cart; }
    public function get_context(): CommerceCheckoutContext { return $this->context; }
    public function get_validation(): CommerceCheckoutValidationResult { return $this->validation; }
    public function get_created_at(): int { return $this->createdat; }
    public function get_currency(): string { return $this->cart->get_totals()->get_total()->get_currency(); }
    public function get_subtotal_minor(): int { return $this->cart->get_totals()->get_subtotal()->get_amount_minor(); }
    public function get_discount_minor(): int { return $this->cart->get_totals()->get_discount()->get_amount_minor(); }
    public function get_tax_minor(): int { return $this->cart->get_totals()->get_tax()->get_amount_minor(); }
    public function get_total_minor(): int { return $this->cart->get_totals()->get_total()->get_amount_minor(); }
    public function is_valid(): bool { return $this->validation->is_valid(); }
}
