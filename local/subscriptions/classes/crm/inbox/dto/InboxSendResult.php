<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxSendResult {

    public function __construct(
        public readonly bool $success,
        public readonly ?string $providermessageid,
        public readonly ?int $sentat,
        public readonly ?string $error = null,
        public readonly ?string $sentfolder = null,
        public readonly ?string $sentcopyerror = null
    ) {
    }
}