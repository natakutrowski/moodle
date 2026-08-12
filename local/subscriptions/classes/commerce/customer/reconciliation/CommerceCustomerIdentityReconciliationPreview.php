<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\reconciliation;

defined('MOODLE_INTERNAL') || die();

/** Read-only impact preview for one identity reconciliation. */
final class CommerceCustomerIdentityReconciliationPreview {
    public function __construct(
        public readonly CommerceCustomerIdentityReconciliationResult $result,
        public readonly int $purchaseupdates = 0,
        public readonly int $grantsupdated = 0,
        public readonly int $digitalaccessupdated = 0,
        public readonly int $guestsessionsupdated = 0,
        public readonly int $legacyrecordsupdated = 0
    ) {}

    public function total_changes(): int {
        return $this->purchaseupdates
            + $this->grantsupdated
            + $this->digitalaccessupdated
            + $this->guestsessionsupdated
            + $this->legacyrecordsupdated;
    }
}
