<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxReplyRequest {

    /**
     * @param string[] $to
     * @param string[] $cc
     * @param string[] $bcc
     */
    public function __construct(
        public readonly int $accountid,
        public readonly int $threadid,
        public readonly array $to,
        public readonly array $cc,
        public readonly array $bcc,
        public readonly string $subject,
        public readonly string $bodytext,
        public readonly ?string $bodyhtml,
        public readonly ?string $inreplyto,
        public readonly array $references,
        public readonly int $actorid
    ) {
    }
}