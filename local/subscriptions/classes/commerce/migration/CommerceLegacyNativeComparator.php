<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Performs a deterministic field-level comparison of persistence snapshots. */
final class CommerceLegacyNativeComparator {
    public function compare(
        CommercePurchasePersistenceSnapshot $expected,
        ?CommercePurchasePersistenceSnapshot $actual
    ): CommerceLegacyNativeComparison {
        if ($actual === null) {
            return new CommerceLegacyNativeComparison(
                CommerceLegacyNativeComparison::STATUS_MISSING_NATIVE,
                ['purchase' => ['expected' => $expected->get_purchase()->to_record(), 'actual' => null]]
            );
        }

        $expectedarray = $this->normalise_snapshot($expected);
        $actualarray = $this->normalise_snapshot($actual);
        $differences = [];

        foreach (['purchase', 'items', 'payments', 'fulfillments'] as $section) {
            if ($expectedarray[$section] !== $actualarray[$section]) {
                $differences[$section] = [
                    'expected' => $expectedarray[$section],
                    'actual' => $actualarray[$section],
                ];
            }
        }

        return new CommerceLegacyNativeComparison(
            $differences === []
                ? CommerceLegacyNativeComparison::STATUS_EQUAL
                : CommerceLegacyNativeComparison::STATUS_DIFFERENT,
            $differences
        );
    }

    private function normalise_snapshot(CommercePurchasePersistenceSnapshot $snapshot): array {
        $purchase = (array)$snapshot->get_purchase()->to_record();
        $this->normalise_json_fields($purchase);

        return [
            'purchase' => $purchase,
            'items' => $this->normalise_records($snapshot->get_items()),
            'payments' => $this->normalise_records($snapshot->get_payments()),
            'fulfillments' => $this->normalise_records($snapshot->get_fulfillments()),
        ];
    }

    private function normalise_records(array $records): array {
        $result = [];
        foreach ($records as $record) {
            $data = (array)$record->to_record();
            $this->normalise_json_fields($data);
            $result[] = $data;
        }
        return $result;
    }

    private function normalise_json_fields(array &$data): void {
        foreach ($data as $key => $value) {
            if (!str_ends_with((string)$key, 'json') || !is_string($value)) {
                continue;
            }
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$key] = $this->sort_recursive($decoded);
            }
        }
    }

    private function sort_recursive(mixed $value): mixed {
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $child) {
            $value[$key] = $this->sort_recursive($child);
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
        return $value;
    }
}