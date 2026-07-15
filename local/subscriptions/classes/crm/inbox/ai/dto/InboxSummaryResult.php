<?php

namespace local_subscriptions\crm\inbox\ai\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxSummaryResult {

    public function __construct(
        public readonly string $summary,
        public readonly array $keypoints,
        public readonly array $pendingquestions,
        public readonly array $customerrequests,
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
        return new self(
            trim(
                (string)($result->data['summary'] ?? '')
            ),
            self::string_array(
                $result->data['keypoints'] ?? []
            ),
            self::string_array(
                $result->data['pendingquestions'] ?? []
            ),
            self::string_array(
                $result->data['customerrequests'] ?? []
            ),
            trim(
                (string)(
                    $result->data['language']
                    ?? 'unknown'
                )
            ),
            max(
                0.0,
                min(1.0, $result->confidence)
            ),
            $result->provider,
            $result->succeeded(),
            $result->warnings
        );
    }

    private static function string_array(
        mixed $value
    ): array {
        if (!is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn(mixed $item): string =>
                        trim((string)$item),
                    $value
                ),
                static fn(string $item): bool =>
                    $item !== ''
            )
        );
    }
}