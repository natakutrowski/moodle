<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents one difference between Commerce and legacy CRM snapshots.
 */
final class CrmCommerceSnapshotDifference {

    public function __construct(
        private readonly string $field,
        private readonly mixed $commercevalue,
        private readonly mixed $legacyvalue
    ) {
        if (trim($field) === '') {
            throw new \coding_exception(
                'A CRM Commerce snapshot difference field cannot be empty.'
            );
        }
    }

    public function get_field(): string {
        return $this->field;
    }

    public function get_commerce_value(): mixed {
        return $this->commercevalue;
    }

    public function get_legacy_value(): mixed {
        return $this->legacyvalue;
    }

    public function to_array(): array {
        return [
            'field' => $this->field,
            'commerce' => $this->commercevalue,
            'legacy' => $this->legacyvalue,
        ];
    }
}