<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessRecommendationInput;

/**
 * Converts recommendation data to planner-safe normalized inputs.
 */
final class CustomerSuccessRecommendationInputFactory {

    /**
     * @param array<string,mixed> $data
     */
    public function from_array(
        array $data
    ): CustomerSuccessRecommendationInput {
        $category = $this->normalize_category(
            (string)($data['category'] ?? '')
        );

        $priority =
            \core_text::strtolower(
                trim(
                    (string)($data['priority'] ?? 'normal')
                )
            );

        return new CustomerSuccessRecommendationInput(
            recommendationid:
                (int)($data['recommendationid'] ?? 0),

            userid:
                (int)($data['userid'] ?? 0),

            recommendationkey:
                (string)(
                    $data['recommendationkey'] ??
                    $data['type'] ??
                    ''
                ),

            title:
                trim(
                    (string)($data['title'] ?? '')
                ),

            description:
                $this->nullable_string(
                    $data['description'] ?? null
                ),

            category:
                $category,

            priority:
                $priority,

            actionkey:
                (string)($data['actionkey'] ?? ''),

            impactscore:
                $this->normalize_score(
                    $data['impactscore'] ?? 50
                ),

            urgencyscore:
                $this->normalize_score(
                    $data['urgencyscore'] ??
                    $this->priority_score(
                        $priority
                    )
                ),

            valuescore:
                $this->normalize_score(
                    $data['valuescore'] ?? 50
                ),

            effortscore:
                $this->normalize_score(
                    $data['effortscore'] ?? 50
                ),

            validuntil:
                $this->nullable_positive_int(
                    $data['validuntil'] ?? null
                ),

            metadata:
                is_array($data['metadata'] ?? null)
                    ? $data['metadata']
                    : []
        );
    }

    /**
     * @param array<int,array<string,mixed>> $records
     * @return CustomerSuccessRecommendationInput[]
     */
    public function from_arrays(
        array $records
    ): array {
        return array_map(
            fn(array $record):
                CustomerSuccessRecommendationInput =>
                    $this->from_array($record),
            $records
        );
    }

    private function normalize_category(
        string $category
    ): string {
        $category =
            \core_text::strtolower(
                trim($category)
            );

        return CustomerSuccessActionCategory::
            is_valid($category)
                ? $category
                : CustomerSuccessActionCategory::OTHER;
    }

    private function normalize_score(
        mixed $value
    ): int {
        return max(
            0,
            min(
                100,
                (int)$value
            )
        );
    }

    private function priority_score(
        string $priority
    ): int {
        return match ($priority) {
            'critical' => 100,
            'urgent' => 85,
            'high' => 70,
            'low' => 25,
            default => 50,
        };
    }

    private function nullable_positive_int(
        mixed $value
    ): ?int {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        $value = (int)$value;

        return $value > 0
            ? $value
            : null;
    }

    private function nullable_string(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string)$value
        );

        return $value !== ''
            ? $value
            : null;
    }
}