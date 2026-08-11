<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\action;

defined('MOODLE_INTERNAL') || die();

final class CommercePurchaseActionResult {
    public function __construct(
        public readonly bool $successful,
        public readonly bool $replayed,
        public readonly string $message,
        public readonly array $details = []
    ) {}
}
