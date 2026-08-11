<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Paginated public catalogue result. */
final class CommerceStorefrontListResult {
    /** @param CommerceStorefrontProduct[] $products */
    public function __construct(
        private readonly array $products,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perpage
    ) {
    }

    /** @return CommerceStorefrontProduct[] */
    public function get_products(): array { return $this->products; }
    public function get_total(): int { return $this->total; }
    public function get_page(): int { return $this->page; }
    public function get_per_page(): int { return $this->perpage; }
}
