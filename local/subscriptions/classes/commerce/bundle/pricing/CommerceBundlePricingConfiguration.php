<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/** Immutable pricing configuration stored in Bundle product metadata. */
final class CommerceBundlePricingConfiguration {
    public const METADATA_STRATEGY = 'bundle_pricing_strategy';
    public const METADATA_DISCOUNT_BPS = 'bundle_discount_bps';

    public function __construct(
        private readonly string $strategy,
        private readonly int $discountbps = 0
    ) {
        CommerceBundlePricingStrategy::require_valid($strategy);

        if ($discountbps < 0 || $discountbps > 10000) {
            throw new \coding_exception('A Bundle discount must be between 0 and 100 percent.');
        }

        if ($strategy !== CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT && $discountbps !== 0) {
            throw new \coding_exception('Only percentage discount pricing accepts a discount value.');
        }
    }

    public static function from_product(CommerceProduct $product): self {
        return new self(
            (string)$product->get_metadata_value(self::METADATA_STRATEGY, CommerceBundlePricingStrategy::FIXED),
            (int)$product->get_metadata_value(self::METADATA_DISCOUNT_BPS, 0)
        );
    }

    public function get_strategy(): string {
        return $this->strategy;
    }

    public function get_discount_bps(): int {
        return $this->discountbps;
    }

    public function get_discount_percent(): string {
        return number_format($this->discountbps / 100, 2, '.', '');
    }

    public function apply_to_metadata(array $metadata): array {
        $metadata[self::METADATA_STRATEGY] = $this->strategy;
        $metadata[self::METADATA_DISCOUNT_BPS] = $this->discountbps;
        return $metadata;
    }

    public function to_array(): array {
        return [
            'strategy' => $this->strategy,
            'discountbps' => $this->discountbps,
            'discountpercent' => $this->get_discount_percent(),
        ];
    }
}
