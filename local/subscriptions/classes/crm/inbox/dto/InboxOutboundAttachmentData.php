<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Binary attachment ready to be passed to the outbound SMTP connector.
 */
final class InboxOutboundAttachmentData {

    public function __construct(
        public readonly string $filename,
        public readonly string $mimetype,
        public readonly string $content,
        public readonly ?string $contentid = null,
        public readonly bool $inline = false
    ) {
    }
}
