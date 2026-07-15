<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxLanguageResult {

    public function __construct(
        public readonly string $language,
        public readonly float $confidence,
        public readonly string $provider,
        public readonly bool $successful,
        public readonly array $warnings = []
    ) {
    }

    public static function from_ai_result(
        InboxAiResult $result
    ): self {
        $language = trim(
            (string)($result->data['language'] ?? '')
        );

        if ($language === '') {
            $language = 'unknown';
        }

        return new self(
            $language,
            max(
                0.0,
                min(1.0, $result->confidence)
            ),
            $result->provider,
            $result->succeeded(),
            $result->warnings
        );
    }
}