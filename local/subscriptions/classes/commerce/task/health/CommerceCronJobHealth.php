<?php

namespace local_subscriptions\commerce\task\health;

defined('MOODLE_INTERNAL') || die();

final class CommerceCronJobHealth {

    public function __construct(
        public readonly string $jobname,
        public readonly string $status,
        public readonly int $lastrunat,
        public readonly int $lastdurationms,
        public readonly int $executions,
        public readonly int $averagedurationms,
        public readonly int $maxdurationms,
        public readonly int $failures,
        public readonly int $warnings,
        public readonly int $lockmisses,
        public readonly array $lastcounters,
    ) {
    }
}
