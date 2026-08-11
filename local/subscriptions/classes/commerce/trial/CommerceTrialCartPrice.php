<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

/** One server-side validated Trial conversion price. */
final class CommerceTrialCartPrice {
    public function __construct(
        private readonly string $productsku,
        private readonly string $currency,
        private readonly int $originalminor,
        private readonly int $discountminor,
        private readonly int $totalminor,
        private readonly int $discountpercent,
        private readonly int $expiresat
    ) {
        if ($productsku === '' || $currency === '') {
            throw new \coding_exception('A Trial cart price requires a product and currency.');
        }
        if ($originalminor < 0 || $discountminor < 0 || $totalminor < 0) {
            throw new \coding_exception('A Trial cart price cannot contain negative amounts.');
        }
        if ($originalminor - $discountminor !== $totalminor) {
            throw new \coding_exception('The Trial cart price amounts are inconsistent.');
        }
    }

    public function get_product_sku(): string { return $this->productsku; }
    public function get_currency(): string { return $this->currency; }
    public function get_original_minor(): int { return $this->originalminor; }
    public function get_discount_minor(): int { return $this->discountminor; }
    public function get_total_minor(): int { return $this->totalminor; }
    public function get_discount_percent(): int { return $this->discountpercent; }
    public function get_expires_at(): int { return $this->expiresat; }
}
