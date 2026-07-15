<?php

namespace local_subscriptions\crm\inbox\ai\safety;

defined('MOODLE_INTERNAL') || die();

final class InboxAiSafetyDecision {

    public function __construct(
        public readonly bool $allowed,
        public readonly array $warnings = [],
        public readonly ?string $reason = null
    ) {
    }
}