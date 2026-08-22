<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxThreadListResult {

    /**
     * @param object[] $threads
     * @param object[] $teams
     * @param object[] $accounts
     */
    public function __construct(
        public readonly InboxThreadCriteria $criteria,
        public readonly array $threads,
        public readonly int $total,
        public readonly array $teams,
        public readonly array $accounts,
        public readonly array $foldercounts
    ) {
    }

    public function has_results(): bool {
        return $this->total > 0;
    }
}