<?php

namespace local_subscriptions\commerce\purchase\subscription;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads subscription plans from the current legacy schema.
 */
final class LegacySubscriptionPlanRepository
    implements SubscriptionPlanRepository {

    public function find(
        int $planid
    ): ?SubscriptionPlanDescriptor {
        global $DB;

        if ($planid <= 0) {
            return null;
        }

        $record = $DB->get_record(
            'subscription_plan',
            [
                'id' => $planid,
            ]
        );

        if (!$record) {
            return null;
        }

        return new SubscriptionPlanDescriptor(
            (int)$record->id,
            $this->resolve_name($record),
            $this->resolve_boolean(
                $record,
                [
                    'is_active',
                    'active',
                ],
                true
            ),
            $this->resolve_boolean(
                $record,
                [
                    'is_trial',
                    'trial',
                ],
                false
            ),
            $this->resolve_boolean(
                $record,
                [
                    'is_recurring',
                    'recurring',
                ],
                false
            ),
            $this->resolve_positive_int(
                $record,
                [
                    'access_scope_id',
                    'accessscopeid',
                ]
            ),
            $this->resolve_string(
                $record,
                [
                    'duration_key',
                    'durationkey',
                ]
            ),
            [
                'legacy_table' => 'subscription_plan',
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
                'internal_name',
            ]
            as $field
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

        return 'Subscription plan #' .
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

    private function resolve_positive_int(
        \stdClass $record,
        array $fields
    ): ?int {
        foreach ($fields as $field) {
            if (!property_exists($record, $field)) {
                continue;
            }

            $value = (int)$record->{$field};

            return $value > 0
                ? $value
                : null;
        }

        return null;
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