<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxContact {

    public function __construct(
        public readonly int $id,
        public readonly ?string $displayname,
        public readonly string $primaryemail,
        public readonly string $normalizedemail,
        public readonly ?int $matcheduserid,
        public readonly string $matchstatus,
        public readonly string $matchsource,
        public readonly float $matchconfidence,
        public readonly bool $matchlocked,
        public readonly ?int $lastmatchedat
    ) {
    }

    public function is_matched(): bool {
        return $this->matcheduserid !== null;
    }

    public function can_be_reconciled(): bool {
        return !$this->matchlocked;
    }
}