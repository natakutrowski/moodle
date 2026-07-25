<?php

namespace local_subscriptions\commerce\task\health;

defined('MOODLE_INTERNAL') || die();

final class CommerceCronHealthSnapshot {

    /** @param CommerceCronJobHealth[] $jobs */
    public function __construct(
        public readonly string $status,
        public readonly int $generatedat,
        public readonly int $windowstart,
        public readonly array $jobs,
        public readonly int $quarantinedpaymentrequests,
    ) {
    }
}
