<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerResult {

    public function __construct(
        public readonly UserExplorerCriteria $criteria,
        public readonly array $users,
        public readonly int $total,
        public readonly array $countries,
        public readonly array $tags,
        public readonly array $visiblecolumns,
        public readonly array $savedviews
    ) {
    }

    public function has_results(): bool {
        return $this->users !== [];
    }

    public function active_filter_count(): int {
        return $this->criteria->active_filter_count();
    }
}