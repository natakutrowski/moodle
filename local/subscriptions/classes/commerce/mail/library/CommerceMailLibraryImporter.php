<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

final class CommerceMailLibraryImporter {
    public function __construct(private readonly CommerceMailLibraryRepository $repository) {}

    public function import_json(string $json, int $userid): \stdClass {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \invalid_parameter_exception('Invalid Mail Studio JSON: ' . $exception->getMessage());
        }
        if (!is_array($payload)
                || ($payload['schema'] ?? '') !== CommerceMailLibrary::SCHEMA
                || (int)($payload['version'] ?? 0) !== CommerceMailLibrary::SCHEMA_VERSION) {
            throw new \invalid_parameter_exception('Unsupported CampusFR Mail Studio export format.');
        }
        $template = is_array($payload['template'] ?? null) ? $payload['template'] : [];
        $translations = is_array($payload['translations'] ?? null) ? $payload['translations'] : [];
        $category = (string)($template['category'] ?? CommerceMailLibrary::CATEGORY_MARKETING);
        if (!in_array($category, CommerceMailLibrary::categories(), true)) {
            throw new \invalid_parameter_exception('Unsupported template category.');
        }

        $normalised = [];
        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $source = is_array($translations[$language] ?? null) ? $translations[$language] : [];
            $content = is_array($source['content'] ?? null) ? $source['content'] : [];
            $mode = (string)($content['mode'] ?? 'html');
            if (in_array($mode, ['legacy_transactional_editorial', 'transactional_editorial'], true)) {
                $introhtml = (string)($content['introhtml'] ?? $content['bodyhtml'] ?? '');
                $normalised[$language] = [
                    'subject' => (string)($source['subject'] ?? ''),
                    'preheader' => (string)($source['preheader'] ?? ''),
                    'bodyhtml' => $introhtml,
                    'document' => [
                        'mode' => 'transactional_editorial',
                        'builderversion' => CommerceMailLibrary::BUILDER_VERSION,
                        'bodyhtml' => $introhtml,
                        'heading' => (string)($content['heading'] ?? ''),
                        'introhtml' => $introhtml,
                        'outrohtml' => (string)($content['outrohtml'] ?? ''),
                        'signaturehtml' => (string)($content['signaturehtml'] ?? ''),
                        'headerimage' => !empty($content['headerimage']),
                        'blocks' => [],
                    ],
                ];
            } else {
                $normalised[$language] = [
                    'subject' => (string)($source['subject'] ?? ''),
                    'preheader' => (string)($source['preheader'] ?? ''),
                    'bodyhtml' => (string)($content['bodyhtml'] ?? ''),
                    'document' => $content,
                ];
            }
        }

        return $this->repository->save([
            'name' => trim((string)($template['name'] ?? '')) . ' — import',
            'category' => $category,
            'status' => CommerceMailLibrary::STATUS_DRAFT,
            'metadata' => [
                'imported_from' => (string)($template['source'] ?? 'unknown'),
                'origin_uuid' => (string)($template['uuid'] ?? ''),
                'origin_mailtype' => (string)($template['mailtype'] ?? ''),
            ],
        ], $normalised, $userid);
    }
}
