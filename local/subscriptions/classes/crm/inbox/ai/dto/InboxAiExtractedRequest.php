<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxAiExtractedRequest {

    public function __construct(
        public readonly string $type,
        public readonly string $description,
        public readonly array $entities = [],
        public readonly float $confidence = 0.0
    ) {
    }
}