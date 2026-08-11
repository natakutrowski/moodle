<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

final class CommercePurchaseFulfillmentSummary {
    public function __construct(
        public readonly string $reference,
        public readonly string $key,
        public readonly string $status,
        public readonly string $idempotencykey,
        public readonly ?string $handlerclass = null,
        public readonly int $attempts = 0,
        public readonly ?string $executionreference = null,
        public readonly ?string $source = null,
        public readonly ?int $actoruserid = null,
        public readonly array $payload = [],
        public readonly ?string $message = null,
        public readonly ?string $errorclass = null,
        public readonly ?int $timestarted = null,
        public readonly ?int $timecompleted = null
    ) {
    }

    public function duration(): ?int {
        if ($this->timestarted === null || $this->timecompleted === null) {
            return null;
        }
        return max(0, $this->timecompleted - $this->timestarted);
    }
}
