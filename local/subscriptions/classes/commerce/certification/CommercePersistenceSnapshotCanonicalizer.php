<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Builds a deterministic array representation of a persistence snapshot. */
final class CommercePersistenceSnapshotCanonicalizer {
    public function canonicalize(CommercePurchasePersistenceSnapshot $snapshot): array {
        return [
            'purchase' => $this->normalise_record((array)$snapshot->get_purchase()->to_record()),
            'items' => $this->normalise_records($snapshot->get_items()),
            'payments' => $this->normalise_records($snapshot->get_payments()),
            'fulfillments' => $this->normalise_records($snapshot->get_fulfillments()),
        ];
    }

    private function normalise_records(array $records): array {
        $result = [];
        foreach ($records as $record) {
            $result[] = $this->normalise_record((array)$record->to_record());
        }
        return $result;
    }

    private function normalise_record(array $record): array {
        foreach ($record as $key => $value) {
            if (str_ends_with((string)$key, 'json') && is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $record[$key] = $this->sort_recursive($decoded);
                }
            }
        }
        ksort($record);
        return $record;
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
