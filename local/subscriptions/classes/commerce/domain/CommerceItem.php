<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents an item sold by the CampusFR Commerce domain.
 *
 * This object is deliberately independent from the current SQL schema.
 * Historical subscription plans and digital products can both be exposed
 * through this common representation.
 */
final class CommerceItem {

    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_DIGITAL = 'digital';
    public const TYPE_BUNDLE = 'bundle';
    public const TYPE_SERVICE = 'service';

    private const VALID_TYPES = [
        self::TYPE_SUBSCRIPTION,
        self::TYPE_DIGITAL,
        self::TYPE_BUNDLE,
        self::TYPE_SERVICE,
    ];

    /**
     * @param string $type Commerce item family.
     * @param string $reference Stable domain reference.
     * @param string $name Human-readable item name.
     * @param int|null $legacyid Identifier from the historical SQL table.
     * @param array $metadata Additional non-critical domain information.
     */
    public function __construct(
        private readonly string $type,
        private readonly string $reference,
        private readonly string $name,
        private readonly ?int $legacyid = null,
        private readonly array $metadata = []
    ) {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \coding_exception('Unsupported Commerce item type: ' . $type);
        }

        if (trim($reference) === '') {
            throw new \coding_exception('A Commerce item reference cannot be empty.');
        }

        if (trim($name) === '') {
            throw new \coding_exception('A Commerce item name cannot be empty.');
        }

        if ($legacyid !== null && $legacyid <= 0) {
            throw new \coding_exception('A legacy Commerce item identifier must be positive.');
        }
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_reference(): string {
        return $this->reference;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_legacy_id(): ?int {
        return $this->legacyid;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }

    public function is_subscription(): bool {
        return $this->type === self::TYPE_SUBSCRIPTION;
    }

    public function is_digital(): bool {
        return $this->type === self::TYPE_DIGITAL;
    }

    public function is_bundle(): bool {
        return $this->type === self::TYPE_BUNDLE;
    }
}