<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxParticipantData {

    public function __construct(
        public readonly string $type,
        public readonly string $email,
        public readonly string $normalizedemail,
        public readonly ?string $displayname = null
    ) {
    }
}