<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\policy;

defined('MOODLE_INTERNAL') || die();

/** Defines the quantities accepted for one catalogue product. */
interface CommerceQuantityPolicy {
    public function get_minimum(): int;

    public function get_maximum(): ?int;

    public function get_step(): int;

    public function allows(int $quantity): bool;

    public function assert_allowed(int $quantity): void;
}
