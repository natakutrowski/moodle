<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryHeaderImageService;

/**
 * Per-grant selection bridge for reusable Mail Studio transactional templates.
 *
 * The selected template is snapshotted so later edits do not change an
 * already-created attribution campaign.
 */
final class CommerceGrantMailStudioSelection {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceMailLibraryRepository $library
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self($db, new CommerceMailLibraryRepository($db));
    }

    /** @return array<int,string> */
    public function template_options(): array {
        $options = [];
        foreach ($this->library->all(
            CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            CommerceMailLibrary::STATUS_ACTIVE
        ) as $template) {
            $options[(int)$template->id] = (string)$template->name;
        }
        return $options;
    }

    /** @return array<string,mixed> */
    public function snapshot(int $templateid): array {
        if ($templateid <= 0) {
            return [];
        }

        $template = $this->library->get($templateid);
        if (
            (string)$template->category !== CommerceMailLibrary::CATEGORY_TRANSACTIONAL
            || (string)$template->status !== CommerceMailLibrary::STATUS_ACTIVE
        ) {
            throw new \invalid_parameter_exception(
                'Only active transactional Mail Studio templates can be selected.'
            );
        }

        $translations = [];
        foreach ($this->library->contents($templateid) as $language => $content) {
            $document = json_decode((string)$content->contentjson, true) ?: [];
            $translations[(string)$language] = [
                'subject' => (string)$content->subject,
                'preheader' => (string)$content->preheader,
                'heading' => (string)($document['heading'] ?? ''),
                'introhtml' => (string)($document['introhtml'] ?? $document['bodyhtml'] ?? ''),
                'outrohtml' => (string)($document['outrohtml'] ?? ''),
                'signaturehtml' => (string)($document['signaturehtml'] ?? ''),
                'headerimage' => !empty($document['headerimage']),
                'contentid' => (int)$content->id,
            ];
        }

        return [
            'templateid' => (int)$template->id,
            'templatename' => (string)$template->name,
            'translations' => $translations,
        ];
    }

    /** @return array<string,mixed>|null */
    public function resolve(array $snapshot, string $language): ?array {
        $translations = is_array($snapshot['translations'] ?? null)
            ? $snapshot['translations']
            : [];
        if ($translations === []) {
            return null;
        }

        $language = strtolower(substr($language, 0, 2));
        $content = $translations[$language]
            ?? $translations['fr']
            ?? $translations['en']
            ?? $translations['ru']
            ?? reset($translations);
        if (!is_array($content)) {
            return null;
        }

        $headerimageurl = !empty($content['headerimage'])
            && !empty($content['contentid'])
            ? CommerceMailLibraryHeaderImageService::url((int)$content['contentid'])
            : '';

        return [
            'subject' => (string)($content['subject'] ?? ''),
            'preheader' => (string)($content['preheader'] ?? ''),
            'heading' => (string)($content['heading'] ?? ''),
            'introhtml' => (string)($content['introhtml'] ?? ''),
            'outrohtml' => (string)($content['outrohtml'] ?? ''),
            'signaturehtml' => (string)($content['signaturehtml'] ?? ''),
            'headerimage' => $headerimageurl !== '',
            'headerimageurl' => $headerimageurl,
            'templateid' => 0,
            'mailstudiotemplateid' => (int)($snapshot['templateid'] ?? 0),
        ];
    }
}
