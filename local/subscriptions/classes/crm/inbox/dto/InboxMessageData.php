<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxMessageData {

    /**
     * @param InboxParticipantData[] $participants
     * @param InboxAttachmentData[] $attachments
     * @param string[] $references
     */
    public function __construct(
        public readonly string $folder,
        public readonly ?string $uidvalidity,
        public readonly ?string $provideruid,
        public readonly ?string $providermessageid,
        public readonly ?string $providerparentid,
        public readonly ?string $providerthreadid,
        public readonly string $direction,
        public readonly string $status,
        public readonly ?string $subject,
        public readonly ?string $bodytext,
        public readonly ?string $bodyhtml,
        public readonly array $headers,
        public readonly ?string $inreplyto,
        public readonly array $references,
        public readonly ?int $receivedat,
        public readonly ?int $sentat,
        public readonly bool $isread,
        public readonly array $participants,
        public readonly array $attachments,
        public readonly ?string $checksum = null
    ) {
    }
}