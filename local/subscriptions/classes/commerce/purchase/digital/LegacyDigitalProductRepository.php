<?php

namespace local_subscriptions\commerce\purchase\digital;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads digital products from the current historical schema.
 */
final class LegacyDigitalProductRepository
    implements DigitalProductRepository {

    public function find(
        int $productid
    ): ?DigitalProductDescriptor {
        global $DB;

        if ($productid <= 0) {
            return null;
        }

        $record = $DB->get_record(
            'subscription_digital_product',
            [
                'id' => $productid,
            ]
        );

        if (!$record) {
            return null;
        }

        return new DigitalProductDescriptor(
            (int)$record->id,
            $this->resolve_name($record),
            $this->resolve_string(
                $record,
                [
                    'slug',
                ]
            ),
            $this->resolve_boolean(
                $record,
                [
                    'is_active',
                    'active',
                    'enabled',
                ],
                true
            ),
            $this->resolve_string(
                $record,
                [
                    'filename',
                    'file_name',
                ]
            ),
            [
                'legacy_table' =>
                    'subscription_digital_product',

                'price_eur' =>
                    $record->price_eur
                        ?? $record->price
                        ?? null,

                'price_rub' =>
                    $record->price_rub
                        ?? null,
            ]
        );
    }

    private function resolve_name(
        \stdClass $record
    ): string {
        foreach (
            [
                'name',
                'title',
                'product_name',
            ] as $field
        ) {
            if (
                isset($record->{$field})
                && trim((string)$record->{$field}) !== ''
            ) {
                return trim(
                    (string)$record->{$field}
                );
            }
        }

        return 'Digital product #' .
            (int)$record->id;
    }

    private function resolve_boolean(
        \stdClass $record,
        array $fields,
        bool $default
    ): bool {
        foreach ($fields as $field) {
            if (property_exists($record, $field)) {
                return !empty(
                    $record->{$field}
                );
            }
        }

        return $default;
    }

    private function resolve_string(
        \stdClass $record,
        array $fields
    ): ?string {
        foreach ($fields as $field) {
            if (!property_exists($record, $field)) {
                continue;
            }

            $value = trim(
                (string)$record->{$field}
            );

            return $value !== ''
                ? $value
                : null;
        }

        return null;
    }
}