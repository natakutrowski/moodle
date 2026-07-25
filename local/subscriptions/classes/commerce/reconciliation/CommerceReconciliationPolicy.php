<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\reconciliation;

defined('MOODLE_INTERNAL') || die();

final class CommerceReconciliationPolicy {
    public function is_enabled(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_native_reconciliation_enabled'));
    }

    public function repair_enabled(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_native_repair_enabled'));
    }
}
