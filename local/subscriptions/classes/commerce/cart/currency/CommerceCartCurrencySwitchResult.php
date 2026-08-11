<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\currency;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;

/** Result of rebuilding a cart in another catalogue currency. */
final class CommerceCartCurrencySwitchResult {
    /** @param array<int, array{sku:string,label:string}> $removeditems */
    public function __construct(
        private readonly CommerceCartSnapshot $snapshot,
        private readonly array $removeditems = [],
        private readonly bool $promotionremoved = false
    ) {
    }

    public function get_snapshot(): CommerceCartSnapshot {
        return $this->snapshot;
    }

    /** @return string[] */
    public function get_removed_skus(): array {
        return array_values(array_map(
            static fn(array $item): string => $item['sku'],
            $this->removeditems
        ));
    }

    /** @return string[] */
    public function get_removed_labels(): array {
        return array_values(array_map(
            static fn(array $item): string => $item['label'],
            $this->removeditems
        ));
    }

    public function was_promotion_removed(): bool {
        return $this->promotionremoved;
    }
}
