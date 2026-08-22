<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxRemoteMessageState {

    public function __construct(
        public readonly string $folder,
        public readonly string $uidvalidity,
        public readonly string $provideruid,
        public readonly ?string $providermessageid,
        public readonly bool $isread,
        public readonly bool $answered,
        public readonly bool $flagged,
        public readonly bool $deleted,
        public readonly bool $draft
    ) {
    }

    public function provider_key(): string {
        return hash(
            'sha256',
            implode('|', [
                $this->folder,
                $this->uidvalidity,
                $this->provideruid,
            ])
        );
    }
}
