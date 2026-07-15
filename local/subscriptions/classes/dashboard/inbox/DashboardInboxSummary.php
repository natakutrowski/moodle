<?php

namespace local_subscriptions\dashboard\inbox;

defined('MOODLE_INTERNAL') || die();

final class DashboardInboxSummary {

    /**
     * @param object[] $recentthreads
     */
    public function __construct(
        public readonly bool $available,
        public readonly int $opencount,
        public readonly int $unassignedcount,
        public readonly int $urgentcount,
        public readonly int $pendingcount,
        public readonly array $recentthreads
    ) {
    }

    public static function unavailable(): self {
        return new self(
            false,
            0,
            0,
            0,
            0,
            []
        );
    }

    public function has_activity(): bool {
        return !empty($this->recentthreads);
    }
}