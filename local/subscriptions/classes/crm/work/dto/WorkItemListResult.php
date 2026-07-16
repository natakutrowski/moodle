<?php

namespace local_subscriptions\crm\work\dto;

defined('MOODLE_INTERNAL') || die();

final class WorkItemListResult {

    public function __construct(
        public readonly WorkItemCriteria $criteria,
        public readonly array $items,
        public readonly int $total,
        public readonly array $teams,
        public readonly array $assignees
    ) {
    }

    public function has_results(): bool {
        return $this->total > 0;
    }
}