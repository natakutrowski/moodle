<?php

namespace local_subscriptions\commerce\purchase\subscription;

defined('MOODLE_INTERNAL') || die();

/**
 * Minimal provider-independent representation of a subscription plan.
 */
final class SubscriptionPlanDescriptor {

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly bool $active,
        private readonly bool $trial,
        private readonly bool $recurring,
        private readonly ?int $accessscopeid,
        private readonly ?string $durationkey,
        private readonly array $metadata = []
    ) {
        if ($id <= 0) {
            throw new \coding_exception(
                'A subscription plan identifier must be positive.'
            );
        }

        if (trim($name) === '') {
            throw new \coding_exception(
                'A subscription plan name cannot be empty.'
            );
        }
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_name(): string {
        return trim($this->name);
    }

    public function is_active(): bool {
        return $this->active;
    }

    public function is_trial(): bool {
        return $this->trial;
    }

    public function is_recurring(): bool {
        return $this->recurring;
    }

    public function get_access_scope_id(): ?int {
        return $this->accessscopeid;
    }

    public function get_duration_key(): ?string {
        if ($this->durationkey === null) {
            return null;
        }

        $value = trim($this->durationkey);

        return $value !== ''
            ? $value
            : null;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}