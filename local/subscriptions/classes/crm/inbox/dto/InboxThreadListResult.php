<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxThreadListResult {

    /**
     * @param object[] $threads
     * @param object[] $teams
     */
    public function __construct(
        public readonly InboxThreadCriteria $criteria,
        public readonly array $threads,
        public readonly int $total,
        public readonly array $teams
    ) {
    }

    public function has_results(): bool {
        return $this->total > 0;
    }
}