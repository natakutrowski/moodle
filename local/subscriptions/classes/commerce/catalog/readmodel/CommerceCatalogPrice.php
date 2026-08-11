<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\contract\CommerceCatalogPriceContract;

final class CommerceCatalogPrice implements CommerceCatalogPriceContract {
    public function __construct(
        private readonly string $currency,
        private readonly int $amountminor,
        private readonly ?string $provider,
        private readonly bool $active,
        private readonly string $origin,
        private readonly ?int $id = null
    ) {
        if ($amountminor < 0) {
            throw new \coding_exception('A catalogue price cannot be negative.');
        }
    }
    public function get_currency(): string { return strtoupper($this->currency); }
    public function get_amount_minor(): int { return $this->amountminor; }
    public function get_provider(): ?string { return $this->provider; }
    public function is_active(): bool { return $this->active; }
    public function get_origin(): string { return $this->origin; }
    public function get_id(): ?int { return $this->id; }
}
