<?php
// CampusFR: persistent, strictly non-publishable Lovable CMS draft storage.
declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

final class CommerceShowroomLovableDraftService {
    public const FILEAREA = 'lovablecmsdraft';
    public const FILENAME = 'compiled.zip';
    public const SETTINGS_KEY = 'campusfr_lovable_draft';

    public static function draft_key(string $designid): string {
        if (!CommerceShowroomBridgeCompileService::valid_id($designid)) {
            throw new \invalid_parameter_exception('Identifiant Lovable invalide.');
        }
        return 'lovable_' . $designid;
    }

    public static function metadata(\stdClass $record): ?array {
        if ((string)$record->status !== 'draft' ||
                !str_starts_with((string)$record->showroomkey, 'lovable_')) {
            return null;
        }
        $settings = json_decode((string)$record->settingsjson, true);
        $metadata = is_array($settings) ? ($settings[self::SETTINGS_KEY] ?? null) : null;
        if (!is_array($metadata) || ($metadata['version'] ?? null) !== 1 ||
                !is_string($metadata['designid'] ?? null) ||
                (string)$record->showroomkey !== self::draft_key($metadata['designid']) ||
                !is_array($metadata['catalogues'] ?? null)) {
            return null;
        }
        return $metadata;
    }

    private static function archive_catalogue(string $archive, string $designid): array {
        $zip = new \ZipArchive();
        if ($zip->open($archive, \ZipArchive::RDONLY) !== true) {
            throw new \invalid_parameter_exception('ZIP compilé illisible.');
        }
        try {
            $name = 'draft/' . $designid . '/content/ru.json';
            $info = $zip->statName($name);
            if (!$info || (int)$info['size'] < 2 || (int)$info['size'] > 600000) {
                throw new \invalid_parameter_exception('Catalogue russe absent du ZIP.');
            }
            $raw = $zip->getFromName($name);
            $decoded = is_string($raw) ? json_decode($raw, true, 32, JSON_THROW_ON_ERROR) : null;
            if (!is_array($decoded) || !isset($decoded['page']) || !is_array($decoded['page']) ||
                    count($decoded['page']) < 1 || count($decoded['page']) > 350) {
                throw new \invalid_parameter_exception('Catalogue Lovable non conforme.');
            }
            $page = [];
            foreach ($decoded['page'] as $key => $value) {
                if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_]{0,80}$/D', $key) ||
                        !is_string($value) || strlen($value) > 3000) {
                    throw new \invalid_parameter_exception('Texte Lovable non conforme.');
                }
                $page[$key] = $value;
            }
            return $page;
        } finally {
            $zip->close();
        }
    }

    /** Create at most one draft for each design. No slug, payments, publish, or enabled blocks. */
    public static function create_from_ready_archive(
        \moodle_database $db,
        \stdClass $user,
        string $archive,
        string $designid
    ): int {
        $key = self::draft_key($designid);
        $repository = new CommerceShowroomCmsRepository($db);
        if ($repository->get_by_key($key)) {
            throw new \invalid_parameter_exception('Un brouillon CMS existe déjà pour ce design.');
        }
        if (!is_file($archive) || is_link($archive) || filesize($archive) > 20971520) {
            throw new \invalid_parameter_exception('ZIP de compilation invalide.');
        }
        // Full untrusted ZIP structural inspection BEFORE the database transaction.
        CommerceShowroomBridgePreviewService::render_frame($archive, $designid);
        $ru = self::archive_catalogue($archive, $designid);
        $metadata = [
            'version' => 1,
            'designid' => $designid,
            'archive_sha256' => hash_file('sha256', $archive),
            'catalogues' => ['ru' => $ru, 'fr' => [], 'en' => []],
            'manual_attributes_to_review' => 17,
            'commerce_connected' => false,
            'translation_status' => 'incomplete',
            'created_at' => time(),
        ];
        $settings = json_encode([self::SETTINGS_KEY => $metadata],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($settings) > 55000) {
            throw new \invalid_parameter_exception('Catalogue trop volumineux pour le CMS.');
        }
        $transaction = $db->start_delegated_transaction();
        try {
            $showroomid = $repository->save([
                'showroomkey' => $key,
                'status' => 'draft',
                'name' => 'Lovable · ' . $designid . ' · à intégrer',
                'template' => 'local_subscriptions/showroom/default',
                'slugfr' => '', 'slugen' => '', 'slugru' => '',
                'productsjson' => '{}', 'settingsjson' => $settings,
            ], (int)$user->id);
            $storage = get_file_storage();
            $context = \context_system::instance();
            $storage->create_file_from_pathname([
                'contextid' => (int)$context->id,
                'component' => 'local_subscriptions',
                'filearea' => self::FILEAREA,
                'itemid' => $showroomid,
                'filepath' => '/',
                'filename' => self::FILENAME,
                'userid' => (int)$user->id,
            ], $archive);
            $transaction->allow_commit();
            return $showroomid;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
    }

    /** @return array{0: \stdClass,1:array} */
    public static function require_draft(\moodle_database $db, int $showroomid): array {
        $record = (new CommerceShowroomCmsRepository($db))->get($showroomid);
        $meta = $record ? self::metadata($record) : null;
        if ($record === null || $meta === null) {
            throw new \invalid_parameter_exception('Brouillon Lovable inexistant ou déjà modifié.');
        }
        return [$record, $meta];
    }

    public static function require_archive(int $showroomid, array $meta): \stored_file {
        $file = get_file_storage()->get_file(
            (int)\context_system::instance()->id, 'local_subscriptions', self::FILEAREA,
            $showroomid, '/', self::FILENAME
        );
        if (!$file || $file->is_directory() || $file->get_filesize() > 20971520 ||
                !preg_match('/^[a-f0-9]{64}$/D', (string)($meta['archive_sha256'] ?? ''))) {
            throw new \invalid_parameter_exception('Archive CMS manquante ou non conforme.');
        }
        return $file;
    }

    /** Materialise only in Moodle's private request temp directory; caller must unlink. */
    public static function materialise_archive(\stored_file $file, array $meta): string {
        $path = make_request_directory() . '/lovable-cms-' . bin2hex(random_bytes(8)) . '.zip';
        if (!$file->copy_content_to($path)) {
            throw new \invalid_parameter_exception('Impossible de préparer le ZIP privé.');
        }
        if (!hash_equals((string)$meta['archive_sha256'], hash_file('sha256', $path))) {
            @unlink($path);
            throw new \invalid_parameter_exception('Empreinte du ZIP CMS incorrecte.');
        }
        return $path;
    }

    public static function displayed_page(array $meta, string $lang): array {
        if (!in_array($lang, ['ru', 'fr', 'en'], true)) {
            throw new \invalid_parameter_exception('Langue incorrecte.');
        }
        $ru = $meta['catalogues']['ru'] ?? [];
        $translated = $meta['catalogues'][$lang] ?? [];
        if (!is_array($ru) || !is_array($translated)) {
            throw new \invalid_parameter_exception('Catalogues CMS invalides.');
        }
        $page = [];
        foreach ($ru as $key => $source) {
            $translation = $translated[$key] ?? '';
            $page[$key] = (is_string($translation) && trim($translation) !== '') ? $translation : $source;
        }
        return ['page' => $page];
    }

    /** Persist only known text keys, never modify products, slugs or status. */
    public static function save_texts(\moodle_database $db, int $id, string $lang, array $texts, int $userid): void {
        if (!in_array($lang, ['ru', 'fr', 'en'], true)) {
            throw new \invalid_parameter_exception('Langue invalide.');
        }
        [$record, $meta] = self::require_draft($db, $id);
        $known = $meta['catalogues']['ru'];
        if (count($texts) > count($known)) {
            throw new \invalid_parameter_exception('Champs inattendus.');
        }
        $values = [];
        foreach ($known as $key => $source) {
            $value = $texts[$key] ?? ($meta['catalogues'][$lang][$key] ?? '');
            if (!is_string($value) || strlen($value) > 3000) {
                throw new \invalid_parameter_exception('Texte invalide : ' . $key);
            }
            if ($lang === 'ru' || trim($value) !== '') {
                $values[$key] = $value;
            }
        }
        $meta['catalogues'][$lang] = $values;
        $settings = json_decode((string)$record->settingsjson, true, 64, JSON_THROW_ON_ERROR);
        $settings[self::SETTINGS_KEY] = $meta;
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > 55000) {
            throw new \invalid_parameter_exception('Traductions trop volumineuses.');
        }
        $db->update_record('local_subs_showroom', (object)[
            'id' => $id, 'settingsjson' => $json,
            'timemodified' => time(), 'usermodified' => $userid,
        ]);
    }
}
