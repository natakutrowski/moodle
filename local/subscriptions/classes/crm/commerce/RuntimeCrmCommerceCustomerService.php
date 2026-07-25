<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable CRM facade.
 *
 * I10C now owns the progressive read decision. Existing callers keep using
 * this class and therefore do not need to know about feature flags.
 */
final class RuntimeCrmCommerceCustomerService {
    public function build_snapshot(int $userid, ?string $email = null): CrmCommerceCustomerSnapshot {
        return (new I10cCrmCommerceCustomerService())
            ->build_snapshot($userid, $email);
    }
}
