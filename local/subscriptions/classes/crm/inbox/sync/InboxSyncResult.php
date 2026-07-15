<?php

namespace local_subscriptions\crm\inbox\sync;

defined('MOODLE_INTERNAL') || die();

final class InboxSyncResult {

    public function __construct(
        public readonly int $fetched,
        public readonly int $created,
        public readonly int $updated,
        public readonly int $skipped,
        public readonly int $errors,
        public readonly ?string $nextcursor,
        public readonly bool $hasmore
    ) {
    }
}