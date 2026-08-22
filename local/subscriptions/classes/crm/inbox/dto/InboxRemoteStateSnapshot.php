<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxRemoteStateSnapshot {

    /**
     * @param InboxRemoteMessageState[] $messages
     */
    public function __construct(
        public readonly string $folder,
        public readonly string $uidvalidity,
        public readonly array $messages
    ) {
    }

    /**
     * @return array<string, InboxRemoteMessageState>
     */
    public function by_uid(): array {
        $result = [];

        foreach ($this->messages as $message) {
            $result[$message->provideruid] = $message;
        }

        return $result;
    }
}
