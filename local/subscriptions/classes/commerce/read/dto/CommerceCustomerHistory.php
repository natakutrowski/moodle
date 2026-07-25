<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\dto;

defined('MOODLE_INTERNAL') || die();

/** Complete Commerce history prepared for CRM and administration consumers. */
final class CommerceCustomerHistory {
    /** @param CommercePurchaseView[] $purchases */
    public function __construct(
        public readonly int $userid,
        public readonly ?string $email,
        public readonly array $purchases
    ) {
    }
}
