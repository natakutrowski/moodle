<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Immutable Native fulfillment attempt displayed by the CRM. */
final class CommercePurchaseFulfillmentAttemptSummary {
    public function __construct(
        public readonly int $id,
        public readonly string $grantreference,
        public readonly string $executionreference,
        public readonly string $granttype,
        public readonly string $handlerclass,
        public readonly string $status,
        public readonly bool $dryrun,
        public readonly string $source,
        public readonly ?int $actoruserid,
        public readonly array $payload,
        public readonly ?string $message,
        public readonly ?string $errorclass,
        public readonly int $timestarted,
        public readonly ?int $timecompleted
    ) {
    }

    public function duration(): ?int {
        return $this->timecompleted === null ? null : max(0, $this->timecompleted - $this->timestarted);
    }
}
