<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Fully rendered message ready for transport.
 */
final class CommerceMailMessage {

    public function __construct(
        private readonly CommerceMailRecipient $recipient,
        private readonly string $subject,
        private readonly string $html,
        private readonly string $text,
        private readonly array $metadata = [],
        private readonly array $attachments = []
    ) {
        if (trim($subject) === '') {
            throw new \coding_exception(
                'A Commerce transactional mail message requires a subject.'
            );
        }

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof CommerceMailAttachment) {
                throw new \coding_exception('Commerce mail attachments must be CommerceMailAttachment instances.');
            }
        }

        if (trim($html) === '' && trim($text) === '') {
            throw new \coding_exception(
                'A Commerce transactional mail message requires HTML or plain-text content.'
            );
        }
    }

    public function get_recipient(): CommerceMailRecipient {
        return $this->recipient;
    }

    public function get_subject(): string {
        return $this->subject;
    }

    public function get_html(): string {
        return $this->html;
    }

    public function get_text(): string {
        return $this->text;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    /** @return CommerceMailAttachment[] */
    public function get_attachments(): array {
        return $this->attachments;
    }
}
