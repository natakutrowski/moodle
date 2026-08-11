<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;

/** Resolved Native identity for a browser payment return. */
final class CommercePaymentReturnContext {
    public function __construct(
        private readonly CommercePaymentAttempt $payment,
        private readonly \stdClass $purchase
    ) {
    }

    public function get_payment(): CommercePaymentAttempt {
        return $this->payment;
    }

    public function get_purchase(): \stdClass {
        return $this->purchase;
    }
}
