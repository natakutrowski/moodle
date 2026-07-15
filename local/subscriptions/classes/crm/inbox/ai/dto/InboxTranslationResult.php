<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxTranslationResult {

    public function __construct(
        public readonly string $translatedtext,
        public readonly string $sourcelanguage,
        public readonly string $targetlanguage,
        public readonly float $confidence,
        public readonly string $provider,
        public readonly bool $successful,
        public readonly array $warnings = []
    ) {
    }

    public static function from_ai_result(
        InboxAiResult $result,
        string $targetlanguage
    ): self {
        return new self(
            trim(
                (string)(
                    $result->data[
                        'translatedtext'
                    ] ?? ''
                )
            ),
            trim(
                (string)(
                    $result->data[
                        'sourcelanguage'
                    ] ?? 'unknown'
                )
            ),
            trim(
                (string)(
                    $result->data[
                        'targetlanguage'
                    ] ?? $targetlanguage
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