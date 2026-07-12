<?php

namespace local_subscriptions\dashboard\issues;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

final class DashboardIssue {

    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly int $count,
        public readonly moodle_url $url,
        public readonly ?moodle_url $primaryactionurl = null,
        public readonly ?string $primaryactionlabel = null,
        public readonly string $severity = 'warning'
    ) {
    }

    public function has_items(): bool {
        return $this->count > 0;
    }

    public function has_primary_action(): bool {
        return $this->primaryactionurl !== null && $this->primaryactionlabel !== null;
    }
}