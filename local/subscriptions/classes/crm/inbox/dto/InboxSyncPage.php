<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxSyncPage {

    /**
     * @param InboxMessageData[] $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly ?string $nextcursor,
        public readonly bool $hasmore
    ) {
    }
}