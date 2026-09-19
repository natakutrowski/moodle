<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\document;

defined('MOODLE_INTERNAL') || die();

/** Durable proof of the legal document set accepted for a Commerce purchase. */
final class CommerceLegalConsentSnapshot {
    public function __construct(
        private readonly CommerceLegalDocumentSet $documents,
        private readonly int $acceptedat,
        private readonly string $source
    ) {
        if ($acceptedat <= 0 || trim($source) === '') {
            throw new \coding_exception('A legal consent snapshot requires acceptance time and source.');
        }
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'schema' => 'legal_consent_v1',
            'accepted' => true,
            'accepted_at' => $this->acceptedat,
            'source' => trim($this->source),
            'document_set' => $this->documents->to_array(),
        ];
    }
}
