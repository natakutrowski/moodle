<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCategory;

final class InboxCategoryResult {

    public function __construct(
        public readonly string $category,
        public readonly float $confidence,
        public readonly array $secondarycategories,
        public readonly array $signals,
        public readonly string $provider,
        public readonly bool $successful,
        public readonly array $warnings = []
    ) {
    }

    public static function from_ai_result(
        InboxAiResult $result
    ): self {
        $category = trim(
            (string)($result->data['category'] ?? '')
        );

        if (!InboxAiCategory::is_valid($category)) {
            $category = InboxAiCategory::OTHER;
        }

        $secondary = $result->data[
            'secondarycategories'
        ] ?? [];

        if (!is_array($secondary)) {
            $secondary = [];
        }

        $secondary = array_values(
            array_filter(
                array_map(
                    'strval',
                    $secondary
                ),
                static fn(string $value): bool =>
                    InboxAiCategory::is_valid($value) &&
                    $value !== $category
            )
        );

        $signals = $result->data['signals']
            ?? [];

        if (!is_array($signals)) {
            $signals = [];
        }

        return new self(
            $category,
            max(
                0.0,
                min(1.0, $result->confidence)
            ),
            array_values(
                array_unique($secondary)
            ),
            array_values($signals),
            $result->provider,
            $result->succeeded(),
            $result->warnings
        );
    }
}