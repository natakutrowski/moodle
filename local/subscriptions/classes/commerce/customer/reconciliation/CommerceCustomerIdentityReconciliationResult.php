<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\reconciliation;

defined('MOODLE_INTERNAL') || die();

/** Immutable result of one Native Commerce customer identity reconciliation. */
final class CommerceCustomerIdentityReconciliationResult {
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_UNCHANGED = 'unchanged';
    public const STATUS_NOT_FOUND = 'not_found';
    public const STATUS_AMBIGUOUS = 'ambiguous';
    public const STATUS_SKIPPED = 'skipped';

    public function __construct(
        public readonly string $status,
        public readonly ?int $purchaseid,
        public readonly ?string $purchasereference,
        public readonly ?string $email,
        public readonly ?int $userid,
        public readonly int $grantsupdated = 0,
        public readonly int $digitalaccessupdated = 0,
        public readonly int $guestsessionsupdated = 0,
        public readonly int $legacyrecordsupdated = 0,
        public readonly array $candidateuserids = []
    ) {}

    public function is_reconciled(): bool {
        return $this->status === self::STATUS_RECONCILED;
    }
}
