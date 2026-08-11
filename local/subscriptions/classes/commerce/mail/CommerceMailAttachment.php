<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/** In-memory attachment carried by a transactional Commerce message. */
final class CommerceMailAttachment {
    public function __construct(
        private readonly string $filename,
        private readonly string $mimetype,
        private readonly string $content
    ) {
        if (trim($filename) === '' || trim($mimetype) === '' || $content === '') {
            throw new \coding_exception('A Commerce mail attachment requires filename, mimetype and content.');
        }
    }
    public function get_filename(): string { return $this->filename; }
    public function get_mimetype(): string { return $this->mimetype; }
    public function get_content(): string { return $this->content; }
}
