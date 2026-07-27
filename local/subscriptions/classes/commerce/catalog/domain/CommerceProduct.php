<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable Native Commerce catalogue product.
 *
 * This model contains the stable commercial identity and default editorial
 * fallback. Localised content, prices, composition and entitlement promises
 * are represented by dedicated catalogue objects.
 */
final class CommerceProduct {

    private readonly string $sku;
    private readonly string $type;
    private readonly string $status;
    private readonly string $name;
    private readonly string $description;

    public function __construct(
        string $sku,
        string $type,
        string $status,
        string $name,
        string $description = '',
        private readonly array $metadata = [],
        private readonly ?int $id = null,
        private readonly ?int $availablefrom = null,
        private readonly ?int $availableuntil = null,
        private readonly ?int $timecreated = null,
        private readonly ?int $timemodified = null
    ) {
        $sku = strtoupper(trim($sku));
        if ($sku === '' || !preg_match('/^[A-Z0-9][A-Z0-9._:-]{1,99}$/', $sku)) {
            throw new \coding_exception('A Commerce product SKU must be a valid stable identifier.');
        }

        if (trim($name) === '') {
            throw new \coding_exception('A Commerce product name cannot be empty.');
        }

        if ($id !== null && $id <= 0) {
            throw new \coding_exception('A Commerce product identifier must be positive.');
        }

        if ($availablefrom !== null && $availablefrom < 0) {
            throw new \coding_exception('A Commerce product availability start cannot be negative.');
        }

        if ($availableuntil !== null && $availableuntil < 0) {
            throw new \coding_exception('A Commerce product availability end cannot be negative.');
        }

        if ($availablefrom !== null && $availableuntil !== null && $availableuntil < $availablefrom) {
            throw new \coding_exception('A Commerce product availability end cannot precede its start.');
        }

        $this->sku = $sku;
        $this->type = CommerceProductType::require_valid($type);
        $this->status = CommerceProductStatus::require_valid($status);
        $this->name = trim($name);
        $this->description = trim($description);
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_sku(): string {
        return $this->sku;
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_description(): string {
        return $this->description;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    public function get_available_from(): ?int {
        return $this->availablefrom;
    }

    public function get_available_until(): ?int {
        return $this->availableuntil;
    }

    public function get_time_created(): ?int {
        return $this->timecreated;
    }

    public function get_time_modified(): ?int {
        return $this->timemodified;
    }

    public function is_active(): bool {
        return $this->status === CommerceProductStatus::ACTIVE;
    }

    public function is_archived(): bool {
        return $this->status === CommerceProductStatus::ARCHIVED;
    }

    public function is_bundle(): bool {
        return $this->type === CommerceProductType::BUNDLE;
    }

    public function is_available_at(int $timestamp): bool {
        if (!$this->is_active()) {
            return false;
        }

        if ($this->availablefrom !== null && $timestamp < $this->availablefrom) {
            return false;
        }

        return $this->availableuntil === null || $timestamp <= $this->availableuntil;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'type' => $this->type,
            'status' => $this->status,
            'name' => $this->name,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'availablefrom' => $this->availablefrom,
            'availableuntil' => $this->availableuntil,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
    }
}
