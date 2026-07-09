<?php

namespace local_subscriptions\crm\intelligence\scoring;

defined('MOODLE_INTERNAL') || die();

final class LeadScoreRuleResult {

    public function __construct(
        public readonly int $commercial = 0,
        public readonly int $engagement = 0,
        public readonly int $risk = 0,
        public readonly string $reason = ''
    ) {
    }
}