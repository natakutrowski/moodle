<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/** Result of an idempotent contribution recording attempt. */
final class CommercePedagogicalXpRecordResult {
    public function __construct(
        private readonly CommercePedagogicalXpContribution $contribution,
        private readonly bool $created
    ) {}

    public function get_contribution(): CommercePedagogicalXpContribution { return $this->contribution; }
    public function was_created(): bool { return $this->created; }
}
