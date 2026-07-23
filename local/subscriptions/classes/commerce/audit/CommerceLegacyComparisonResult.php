<?php

namespace local_subscriptions\commerce\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Detailed comparison result between legacy rows and Commerce objects.
 */
final class CommerceLegacyComparisonResult {

    /** @var array<int,array<string,mixed>> */
    private array $differences = [];

    /** @var array<string,int> */
    private array $counters = [];

    public function increment(
        string $key,
        int $amount = 1
    ): void {
        if (!isset($this->counters[$key])) {
            $this->counters[$key] = 0;
        }

        $this->counters[$key] += $amount;
    }

    public function add_difference(
        string $type,
        int $legacyid,
        string $field,
        mixed $legacyvalue,
        mixed $commercevalue
    ): void {
        $this->differences[] = [
            'type' => $type,
            'legacyid' => $legacyid,
            'field' => $field,
            'legacy' => $legacyvalue,
            'commerce' => $commercevalue,
        ];

        $this->increment('differences');
    }

    public function get_counters(): array {
        return $this->counters;
    }

    public function get_differences(): array {
        return $this->differences;
    }

    public function has_differences(): bool {
        return $this->differences !== [];
    }

    public function to_array(): array {
        return [
            'counters' => $this->counters,
            'differences' => $this->differences,
            'summary' => [
                'differences' => count($this->differences),
            ],
        ];
    }
}