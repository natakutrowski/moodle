<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use moodle_database;

final class CommerceMailLibraryExporter {
    public function __construct(
        private readonly moodle_database $db,
        private readonly CommerceMailLibraryRepository $library
    ) {}

    public function native(int $id): array {
        $record = $this->library->get($id);
        $contents = $this->library->contents($id);
        $translations = [];
        foreach ($contents as $language => $content) {
            $translations[$language] = [
                'subject' => (string)$content->subject,
                'preheader' => (string)$content->preheader,
                'content' => json_decode((string)$content->contentjson, true) ?: [],
            ];
        }
        return $this->envelope([
            'name' => (string)$record->name,
            'category' => (string)$record->category,
            'status' => (string)$record->status,
            'builderVersion' => (int)$record->builderversion,
            'source' => CommerceMailLibrary::SOURCE_NATIVE,
            'uuid' => (string)$record->templateuuid,
            'metadata' => json_decode((string)$record->metadatajson, true) ?: [],
        ], $translations);
    }

    public function transactional(string $mailtype): array {
        if (!in_array($mailtype, CommerceMailType::all(), true)) {
            throw new \invalid_parameter_exception('Invalid transactional mail type.');
        }
        $repository = new CommerceMailTemplateRepository($this->db);
        $translations = [];
        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $record = $repository->get($mailtype, $language);
            $data = $record ? (array)$record : CommerceMailTemplateDefaults::get($mailtype, $language);
            $translations[$language] = [
                'subject' => (string)($data['subject'] ?? ''),
                'preheader' => (string)($data['preheader'] ?? ''),
                'content' => [
                    'mode' => 'transactional_editorial',
                    'heading' => (string)($data['heading'] ?? ''),
                    'introhtml' => (string)($data['introhtml'] ?? ''),
                    'outrohtml' => (string)($data['outrohtml'] ?? ''),
                    'signaturehtml' => (string)($data['signaturehtml'] ?? ''),
                    'headerimage' => !empty($data['headerimage']),
                ],
            ];
        }
        return $this->envelope([
            'name' => $mailtype,
            'category' => CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'builderVersion' => CommerceMailLibrary::BUILDER_VERSION,
            'source' => CommerceMailLibrary::SOURCE_TRANSACTIONAL,
            'mailtype' => $mailtype,
            'metadata' => ['runtime' => 'legacy_transactional'],
        ], $translations);
    }

    private function envelope(array $template, array $translations): array {
        return [
            'schema' => CommerceMailLibrary::SCHEMA,
            'version' => CommerceMailLibrary::SCHEMA_VERSION,
            'exportedAt' => time(),
            'template' => $template,
            'translations' => $translations,
        ];
    }
}
