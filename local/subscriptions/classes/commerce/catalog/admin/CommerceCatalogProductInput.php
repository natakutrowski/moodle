<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/** Normalised input used by the unified CRM product editor. */
final class CommerceCatalogProductInput {
    public function __construct(
        private readonly string $sku,
        private readonly string $type,
        private readonly string $status,
        private readonly string $name,
        private readonly string $description = '',
        private readonly ?int $availablefrom = null,
        private readonly ?int $availableuntil = null,
        private readonly ?array $metadata = null
    ) {
    }

    public function to_product(?CommerceProduct $existing = null): CommerceProduct {
        return new CommerceProduct(
            $this->sku,
            $this->type,
            $this->status,
            $this->name,
            $this->description,
            $this->metadata ?? $existing?->get_metadata() ?? [],
            $existing?->get_id(),
            $this->availablefrom,
            $this->availableuntil,
            $existing?->get_time_created(),
            $existing?->get_time_modified()
        );
    }
}
