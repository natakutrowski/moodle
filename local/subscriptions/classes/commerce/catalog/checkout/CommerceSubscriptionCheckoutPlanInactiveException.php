<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\checkout;

defined('MOODLE_INTERNAL') || die();

/** Signals that a Legacy plan requested through the compatibility route is inactive. */
final class CommerceSubscriptionCheckoutPlanInactiveException extends \RuntimeException {
    public function __construct(private readonly int $scopeId) {
        parent::__construct('The requested Subscription plan is inactive.');
    }

    public function get_scope_id(): int {
        return $this->scopeId;
    }
}
