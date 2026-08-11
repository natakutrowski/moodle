<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\contract\CommerceCatalogProductContract;
use local_subscriptions\commerce\catalog\status\CommerceCatalogStatusSnapshot;

final class CommerceCatalogProductSummary implements CommerceCatalogProductContract {
    public function __construct(
        private readonly ?int $id,
        private readonly string $sku,
        private readonly string $name,
        private readonly string $description,
        private readonly string $type,
        private readonly string $origin,
        private readonly CommerceCatalogStatusSnapshot $status,
        private readonly array $prices = [],
        private readonly array $fulfillments = [],
        private readonly array $metadata = [],
        private readonly ?int $availablefrom = null,
        private readonly ?int $availableuntil = null
    ) {
    }
    public function get_id(): ?int { return $this->id; }
    public function get_sku(): string { return $this->sku; }
    public function get_name(): string { return $this->name; }
    public function get_description(): string { return $this->description; }
    public function get_type(): string { return $this->type; }
    public function get_editorial_status(): string { return $this->status->get_editorial(); }
    public function get_visibility(): string { return $this->status->get_visibility(); }
    public function get_availability(): string { return $this->status->get_availability(); }
    public function get_technical_state(): string { return $this->status->get_technical(); }
    public function get_origin(): string { return $this->origin; }
    public function get_available_from(): ?int { return $this->availablefrom; }
    public function get_available_until(): ?int { return $this->availableuntil; }
    public function get_metadata(): array { return $this->metadata; }
    public function get_prices(): array { return $this->prices; }
    public function get_fulfillments(): array { return $this->fulfillments; }
    public function to_array(): array {
        return array_merge([
            'id' => $this->id, 'sku' => $this->sku, 'name' => $this->name,
            'description' => $this->description, 'type' => $this->type, 'origin' => $this->origin,
            'availablefrom' => $this->availablefrom, 'availableuntil' => $this->availableuntil,
            'pricecount' => count($this->prices), 'fulfillmentcount' => count($this->fulfillments),
        ], $this->status->to_array());
    }
}
