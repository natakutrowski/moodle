<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\transactional;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryHeaderImageService;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;

/**
 * N5.5 bridge between legacy transactional editorial storage and Mail Studio.
 *
 * A transactional mail type can have one authoritative Mail Studio template,
 * identified by a stable runtime key. Until a type is migrated, the legacy
 * repository/defaults remain the runtime fallback.
 */
final class CommerceTransactionalMailStudioBridge {
    public const RUNTIME_PREFIX = 'transactional:';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceMailLibraryRepository $library,
        private readonly CommerceMailTemplateRepository $legacy
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self(
            $db,
            new CommerceMailLibraryRepository($db),
            new CommerceMailTemplateRepository($db)
        );
    }

    /** @return string[] */
    public static function supported_types(): array {
        return array_values(array_filter(
            CommerceMailType::all(),
            static fn(string $type): bool => $type !== CommerceMailType::PERSONAL_OFFER
        ));
    }

    public static function runtime_key(string $mailtype): string {
        if (!in_array($mailtype, self::supported_types(), true)) {
            throw new \invalid_parameter_exception('Invalid transactional mail type.');
        }
        return self::RUNTIME_PREFIX . $mailtype;
    }

    public function template(string $mailtype): ?\stdClass {
        return $this->library->get_by_runtime_key(self::runtime_key($mailtype));
    }

    public function migrate(string $mailtype, int $userid): \stdClass {
        $existing = $this->template($mailtype);
        if ($existing !== null) {
            return $existing;
        }

        $translations = [];
        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $legacy = $this->legacy->get($mailtype, $language);
            $effective = $legacy && !empty($legacy->enabled)
                ? (array)$legacy
                : CommerceMailTemplateDefaults::get($mailtype, $language);

            $translations[$language] = [
                'subject' => (string)($effective['subject'] ?? ''),
                'preheader' => (string)($effective['preheader'] ?? ''),
                'bodyhtml' => (string)($effective['introhtml'] ?? ''),
                'document' => [
                    'mode' => 'transactional_editorial',
                    'builderversion' => CommerceMailLibrary::BUILDER_VERSION,
                    'bodyhtml' => (string)($effective['introhtml'] ?? ''),
                    'heading' => (string)($effective['heading'] ?? ''),
                    'introhtml' => (string)($effective['introhtml'] ?? ''),
                    'outrohtml' => (string)($effective['outrohtml'] ?? ''),
                    'signaturehtml' => (string)($effective['signaturehtml'] ?? ''),
                    'headerimage' => !empty($effective['headerimage']),
                    'blocks' => [],
                ],
            ];
        }

        $template = $this->library->save([
            'name' => CommerceMailAdminPresentation::type_label($mailtype),
            'category' => CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'runtimekey' => self::runtime_key($mailtype),
            'metadata' => [
                'foundation' => 'N5.6',
                'editor' => 'mail_builder',
                'runtime' => 'transactional',
                'mailtype' => $mailtype,
                'migrated_from' => 'local_subs_commerce_mail_tpl',
            ],
        ], $translations, $userid);

        // N5.6 also migrates the localized header-image binaries into the
        // unified Mail Studio file area.
        $contents = $this->library->contents((int)$template->id);
        foreach (CommerceMailLibrary::LANGUAGES as $language) {
            $legacy = $this->legacy->get($mailtype, $language);
            $content = $contents[$language] ?? null;
            if ($legacy && $content && !empty($legacy->headerimage)) {
                CommerceMailLibraryHeaderImageService::copy_from_legacy(
                    (int)$legacy->id,
                    (int)$content->id
                );
            }
        }

        return $template;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function resolve(string $mailtype, string $language): ?array {
        if (!in_array($mailtype, self::supported_types(), true)) {
            return null;
        }
        $template = $this->template($mailtype);
        if ($template === null
                || (string)$template->status !== CommerceMailLibrary::STATUS_ACTIVE) {
            return null;
        }

        $contents = $this->library->contents((int)$template->id);
        $content = $contents[$language] ?? $contents['fr'] ?? null;
        if ($content === null) {
            return null;
        }

        $document = json_decode((string)$content->contentjson, true) ?: [];
        if ((string)($document['mode'] ?? '') !== 'transactional_editorial') {
            return null;
        }

        $headerimageurl = !empty($document['headerimage'])
            ? CommerceMailLibraryHeaderImageService::url((int)$content->id)
            : '';

        return [
            'subject' => (string)$content->subject,
            'preheader' => (string)$content->preheader,
            'heading' => (string)($document['heading'] ?? ''),
            'introhtml' => (string)($document['introhtml'] ?? $document['bodyhtml'] ?? ''),
            'outrohtml' => (string)($document['outrohtml'] ?? ''),
            'signaturehtml' => (string)($document['signaturehtml'] ?? ''),
            'headerimage' => $headerimageurl !== '',
            'headerimageurl' => $headerimageurl,
            'templateid' => 0,
            'mailstudiotemplateid' => (int)$template->id,
        ];
    }

}
