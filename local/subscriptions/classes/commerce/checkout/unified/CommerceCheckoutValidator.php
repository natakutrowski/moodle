<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;

/** Validates cart invariants before a transaction is frozen. */
final class CommerceCheckoutValidator {
    public function validate(CommerceCartSnapshot $cart, CommerceCheckoutContext $context): CommerceCheckoutValidationResult {
        $issues = [];
        if ($cart->get_cart()->is_empty() || $cart->get_items() === []) {
            $issues[] = new CommerceCheckoutValidationIssue('empty_cart', 'The checkout cart is empty.');
        }
        if ($cart->get_cart()->get_customer_id() !== $context->get_customer_id()) {
            $issues[] = new CommerceCheckoutValidationIssue('customer_mismatch', 'The cart belongs to another customer.');
        }
        if ($cart->get_cart()->get_currency() !== $context->get_currency()) {
            $issues[] = new CommerceCheckoutValidationIssue('currency_mismatch', 'The cart currency differs from checkout currency.');
        }
        foreach ($cart->get_messages() as $message) {
            if (!$this->is_blocking_message($message->get_code(), $message->get_level())) {
                continue;
            }

            $issues[] = new CommerceCheckoutValidationIssue(
                'cart_' . $message->get_code(),
                'The cart contains a blocking calculation message.',
                $message->get_context()
            );
        }
        return new CommerceCheckoutValidationResult($issues);
    }

    private function is_blocking_message(string $code, string $level): bool {
        if ($level === 'error') {
            return true;
        }

        // A rejected promotion is informational at checkout time: the rejected
        // adjustment is not included in totals, so the undiscounted cart remains valid.
        if ($level === 'warning' && str_starts_with($code, 'promotion_')) {
            return false;
        }

        return $level === 'warning';
    }
}
