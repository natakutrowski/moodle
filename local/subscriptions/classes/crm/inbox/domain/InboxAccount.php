<?php

namespace local_subscriptions\crm\inbox\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAccount {

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $provider,
        public readonly bool $enabled,
        public readonly ?string $credentialkey,
        public readonly array $configuration,
        public readonly array $syncstate,
        public readonly ?int $lastsyncedat,
        public readonly ?int $lasterrorat,
        public readonly ?string $lasterror
    ) {
    }
}