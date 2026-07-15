<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxSuggestedReply {

    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $language,
        public readonly string $tone,
        public readonly float $confidence,
        public readonly array $warnings = [],
        public readonly bool $requiresreview = true
    ) {
    }
}