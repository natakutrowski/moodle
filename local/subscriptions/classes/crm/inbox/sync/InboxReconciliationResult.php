<?php

namespace local_subscriptions\crm\inbox\sync;

defined('MOODLE_INTERNAL') || die();

final class InboxReconciliationResult {

    public function __construct(
        public readonly int $checked,
        public readonly int $updated,
        public readonly int $moved,
        public readonly int $missing,
        public readonly int $errors,
        public readonly string $uidvalidity
    ) {
    }
}
