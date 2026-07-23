<?php

namespace local_subscriptions\commerce\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;

/**
 * One requested Commerce item with its locked financial information.
 *
 * The unit amount is stored in minor currency units to avoid floating-point
 * calculations during payment preparation.
 */
final class CommercePurchaseRequestItem {

    public function __construct(
        private readonly CommerceItem $item,
        private readonly int $quantity,
        private readonly int $unitamountminor,
        private readonly string $currency,
        private readonly array $metadata = []
    ) {
        if ($quantity <= 0) {
            throw new \coding_exception(
                'A Commerce purchase request item quantity must be positive.'
            );
        }

        if ($unitamountminor < 0) {
            throw new \coding_exception(
                'A Commerce purchase request item amount cannot be negative.'
            );
        }

        $currency = strtoupper(
            trim($currency)
        );

        if (
            !preg_match(
                '/^[A-Z]{3}$/',
                $currency
            )
        ) {
            throw new \coding_exception(
                'A Commerce purchase request item currency must use ISO 4217 format.'
            );
        }
    }

    public function get_item(): CommerceItem {
        return $this->item;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_unit_amount_minor(): int {
        return $this->unitamountminor;
    }

    public function get_currency(): string {
        return strtoupper(
            trim($this->currency)
        );
    }

    public function get_total_amount_minor(): int {
        return $this->unitamountminor
            * $this->quantity;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function is_free(): bool {
        return $this->get_total_amount_minor() === 0;
    }
}