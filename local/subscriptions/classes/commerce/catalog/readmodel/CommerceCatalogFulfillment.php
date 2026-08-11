<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\contract\CommerceCatalogFulfillmentContract;

final class CommerceCatalogFulfillment implements CommerceCatalogFulfillmentContract {
    public function __construct(
        private readonly string $type,
        private readonly string $resourcekey,
        private readonly ?int $durationseconds,
        private readonly int $quantity,
        private readonly array $configuration,
        private readonly string $origin
    ) {
        if ($quantity < 1) {
            throw new \coding_exception('A catalogue fulfillment quantity must be positive.');
        }
    }
    public function get_type(): string { return $this->type; }
    public function get_resource_key(): string { return $this->resourcekey; }
    public function get_duration_seconds(): ?int { return $this->durationseconds; }
    public function get_quantity(): int { return $this->quantity; }
    public function get_configuration(): array { return $this->configuration; }
    public function get_origin(): string { return $this->origin; }
}
