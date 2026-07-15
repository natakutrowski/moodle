<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxToneResult {

    public function __construct(
        public readonly string $text,
        public readonly string $tone,
        public readonly string $language,
        public readonly float $confidence,
        public readonly string $provider,
        public readonly bool $successful,
        public readonly array $warnings = []
    ) {
    }

    public static function from_ai_result(
        InboxAiResult $result,
        string $tone,
        string $language
    ): self {
        return new self(
            trim(
                (string)(
                    $result->data['text']
                    ?? ''
                )
            ),
            trim(
                (string)(
                    $result->data['tone']
                    ?? $tone
                )
            ),
            trim(
                (string)(
                    $result->data['language']
                    ?? $language
                )
            ),
            max(
                0.0,
                min(
                    1.0,
                    $result->confidence
                )
            ),
            $result->provider,
            $result->succeeded(),
            $result->warnings
        );
    }
}