<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\policy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\exception\CommerceCartQuantityException;

/** Quantity policy supporting bounded or unbounded integer ranges. */
final class CommerceRangeQuantityPolicy implements CommerceQuantityPolicy {
    public function __construct(
        private readonly int $minimum = 1,
        private readonly ?int $maximum = null,
        private readonly int $step = 1
    ) {
        if ($minimum < 1) {
            throw new \coding_exception('A Commerce quantity minimum must be at least one.');
        }

        if ($maximum !== null && $maximum < $minimum) {
            throw new \coding_exception('A Commerce quantity maximum cannot be lower than its minimum.');
        }

        if ($step < 1) {
            throw new \coding_exception('A Commerce quantity step must be at least one.');
        }
    }

    public function get_minimum(): int {
        return $this->minimum;
    }

    public function get_maximum(): ?int {
        return $this->maximum;
    }

    public function get_step(): int {
        return $this->step;
    }

    public function allows(int $quantity): bool {
        if ($quantity < $this->minimum) {
            return false;
        }

        if ($this->maximum !== null && $quantity > $this->maximum) {
            return false;
        }

        return ($quantity - $this->minimum) % $this->step === 0;
    }

    public function assert_allowed(int $quantity): void {
        if (!$this->allows($quantity)) {
            throw new CommerceCartQuantityException(
                $quantity,
                $this->minimum,
                $this->maximum,
                $this->step
            );
        }
    }
}
