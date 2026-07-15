<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxAiClassification {

    public function __construct(
        public readonly ?string $language,
        public readonly ?string $category,
        public readonly ?string $urgency,
        public readonly float $confidence,
        public readonly array $signals = []
    ) {
    }
}