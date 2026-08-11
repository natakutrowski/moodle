<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;

/**
 * Result of persisting the checkout purchase and its operational payment attempt.
 */
final class CommerceCheckoutPersistenceResult {
    public function __construct(
        private readonly int $purchaseid,
        private readonly ?CommercePaymentAttempt $paymentattempt
    ) {
        if ($purchaseid < 0) {
            throw new \InvalidArgumentException('A persisted Commerce purchase identifier cannot be negative.');
        }
    }

    public function get_purchase_id(): int {
        return $this->purchaseid;
    }

    public function get_payment_attempt(): ?CommercePaymentAttempt {
        return $this->paymentattempt;
    }
}
