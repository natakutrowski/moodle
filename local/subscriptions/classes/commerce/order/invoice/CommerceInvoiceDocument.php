<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\invoice;

defined('MOODLE_INTERNAL') || die();

/** Immutable generated invoice document. */
final class CommerceInvoiceDocument {
    public function __construct(
        private readonly string $filename,
        private readonly string $content,
        private readonly string $mimetype = 'application/pdf'
    ) {
        if (trim($filename) === '' || $content === '') {
            throw new \coding_exception('A Commerce invoice document requires a filename and content.');
        }
    }

    public function get_filename(): string { return $this->filename; }
    public function get_content(): string { return $this->content; }
    public function get_mimetype(): string { return $this->mimetype; }
}
