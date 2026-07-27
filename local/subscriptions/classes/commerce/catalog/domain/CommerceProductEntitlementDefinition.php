<?php

namespace local_subscriptions\commerce\catalog\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable promise of a right granted when a product is purchased.
 *
 * This is a catalogue definition, not a customer entitlement instance.
 */
final class CommerceProductEntitlementDefinition {

    private readonly string $productsku;
    private readonly string $type;
    private readonly string $resourcekey;

    public function __construct(
        string $productsku,
        string $type,
        string $resourcekey,
        private readonly ?int $durationseconds = null,
        private readonly int $quantity = 1,
        private readonly array $configuration = [],
        private readonly int $sortorder = 0,
        private readonly ?int $id = null
    ) {
        $productsku = strtoupper(trim($productsku));
        $type = strtolower(trim($type));
        $resourcekey = trim($resourcekey);

        if ($productsku === '') {
            throw new \coding_exception('A Commerce entitlement definition requires a product SKU.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) {
            throw new \coding_exception('A Commerce entitlement type must be a valid machine identifier.');
        }

        if ($resourcekey === '') {
            throw new \coding_exception('A Commerce entitlement definition resource key cannot be empty.');
        }

        if ($durationseconds !== null && $durationseconds <= 0) {
            throw new \coding_exception('A Commerce entitlement duration must be positive or null for lifetime.');
        }

        if ($quantity <= 0) {
            throw new \coding_exception('A Commerce entitlement definition quantity must be positive.');
        }

        if ($sortorder < 0) {
            throw new \coding_exception('A Commerce entitlement definition sort order cannot be negative.');
        }

        if ($id !== null && $id <= 0) {
            throw new \coding_exception('A Commerce entitlement definition identifier must be positive.');
        }

        $this->productsku = $productsku;
        $this->type = $type;
        $this->resourcekey = $resourcekey;
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_product_sku(): string {
        return $this->productsku;
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_resource_key(): string {
        return $this->resourcekey;
    }

    public function get_duration_seconds(): ?int {
        return $this->durationseconds;
    }

    public function is_lifetime(): bool {
        return $this->durationseconds === null;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_configuration(): array {
        return $this->configuration;
    }

    public function get_sort_order(): int {
        return $this->sortorder;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'productsku' => $this->productsku,
            'type' => $this->type,
            'resourcekey' => $this->resourcekey,
            'durationseconds' => $this->durationseconds,
            'quantity' => $this->quantity,
            'configuration' => $this->configuration,
            'sortorder' => $this->sortorder,
        ];
    }
}
