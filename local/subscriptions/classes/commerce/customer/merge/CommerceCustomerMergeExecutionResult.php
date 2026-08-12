<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of one completed transactional merge.
 */
final class CommerceCustomerMergeExecutionResult {
    /**
     * @param int[] $sourceuserids
     * @param array<string,int> $transfers
     */
    public function __construct(
        public readonly int $mergeid,
        public readonly string $mergeuuid,
        public readonly int $targetuserid,
        public readonly array $sourceuserids,
        public readonly array $transfers
    ) {
    }
}
