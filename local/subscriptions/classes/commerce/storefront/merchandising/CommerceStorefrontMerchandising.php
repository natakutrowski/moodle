<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\merchandising;

defined('MOODLE_INTERNAL') || die();

/** Immutable public merchandising configuration for one Storefront product. */
final class CommerceStorefrontMerchandising {
    /**
     * @param string[] $badges
     * @param array<string, array<string, int|null>> $promotions
     */
    public function __construct(
        private readonly bool $featured = false,
        private readonly int $displayorder = 1000,
        private readonly array $badges = [],
        private readonly array $promotions = []
    ) {
    }

    public function is_featured(): bool {
        return $this->featured;
    }

    public function get_display_order(): int {
        return $this->displayorder;
    }

    /** @return string[] */
    public function get_badges(): array {
        return $this->badges;
    }

    /** @return array<string, int|null>|null */
    public function get_promotion(string $currency): ?array {
        return $this->promotions[strtoupper(trim($currency))] ?? null;
    }
}
