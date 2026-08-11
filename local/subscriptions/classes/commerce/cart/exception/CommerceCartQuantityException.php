<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\exception;

defined('MOODLE_INTERNAL') || die();

/** Raised when a requested quantity violates the product policy. */
final class CommerceCartQuantityException extends \RuntimeException {
    public function __construct(
        private readonly int $quantity,
        private readonly int $minimum,
        private readonly ?int $maximum,
        private readonly int $step
    ) {
        parent::__construct('The requested Commerce cart quantity is not allowed.');
    }

    public function get_quantity(): int {
        return $this->quantity;
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
}
