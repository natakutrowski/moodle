<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

/**
 * One read-only similarity suggestion between two Moodle accounts.
 */
final class CommerceCustomerIdentitySimilarityMatch {
    /**
     * @param string[] $reasons
     */
    public function __construct(
        public readonly \stdClass $first,
        public readonly \stdClass $second,
        public readonly int $score,
        public readonly array $reasons
    ) {
        if ($first->id === $second->id) {
            throw new \coding_exception(
                'A customer identity similarity cannot compare an account with itself.'
            );
        }
        if ($score < 0 || $score > 100) {
            throw new \coding_exception(
                'A customer identity similarity score must be between 0 and 100.'
            );
        }
    }

    public function key(): string {
        $ids = [(int)$this->first->id, (int)$this->second->id];
        sort($ids, SORT_NUMERIC);
        return $ids[0] . ':' . $ids[1];
    }
}
