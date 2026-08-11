<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\policy;

defined('MOODLE_INTERNAL') || die();

/** Current default policy for access products and digital downloads. */
final class CommerceSingleQuantityPolicy implements CommerceQuantityPolicy {
    private CommerceRangeQuantityPolicy $range;

    public function __construct() {
        $this->range = new CommerceRangeQuantityPolicy(1, 1, 1);
    }

    public function get_minimum(): int {
        return 1;
    }

    public function get_maximum(): ?int {
        return 1;
    }

    public function get_step(): int {
        return 1;
    }

    public function allows(int $quantity): bool {
        return $this->range->allows($quantity);
    }

    public function assert_allowed(int $quantity): void {
        $this->range->assert_allowed($quantity);
    }
}
