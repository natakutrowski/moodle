<?php

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

final class InboxAttachmentData {

    public function __construct(
        public readonly ?string $providerattachmentid,
        public readonly string $filename,
        public readonly ?string $mimetype,
        public readonly int $filesize,
        public readonly ?string $contentid = null,
        public readonly bool $inline = false
    ) {
    }
}