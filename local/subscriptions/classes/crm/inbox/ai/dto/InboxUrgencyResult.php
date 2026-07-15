<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiUrgency;

final class InboxUrgencyResult {

    public function __construct(
        public readonly string $urgency,
        public readonly float $confidence,
        public readonly array $signals,
        public readonly string $provider,
        public readonly bool $successful,
        public readonly array $warnings = []
    ) {
    }

    public static function from_ai_result(
        InboxAiResult $result
    ): self {
        $urgency = trim(
            (string)($result->data['urgency'] ?? '')
        );

        if (!InboxAiUrgency::is_valid($urgency)) {
            $urgency = InboxAiUrgency::NORMAL;
        }

        $signals = $result->data['signals']
            ?? [];

        if (!is_array($signals)) {
            $signals = [];
        }

        return new self(
            $urgency,
            max(
                0.0,
                min(1.0, $result->confidence)
            ),
            array_values($signals),
            $result->provider,
            $result->succeeded(),
            $result->warnings
        );
    }
}