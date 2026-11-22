<?php
// CampusFR D2.5 — Portable story ZIP: editorial metadata and private files only.
declare(strict_types=1);
namespace local_subscriptions\crm\library;

defined('MOODLE_INTERNAL') || die();

final class ReaderStoryTransferService {
    private const SCHEMA = 'campusfr.story.v1';
    private const COMPONENT = 'local_campusreader';
    private const MAXARCHIVE = 268435456; // 256 MiB compressed upload.
    private const MAXTOTAL = 536870912; // 512 MiB uncompressed.
    private const AREALIMITS = ['cover' => 8388608, 'pageimage' => 8388608,
        'narration' => 68157440, 'video' => 209715200, 'pdf' => 36700160];
    private const EXTENSIONS = [
        'cover' => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'],
        'pageimage' => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'],
        'narration' => ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'ogg' => 'audio/ogg'],
        'video' => ['mp4' => 'video/mp4', 'webm' => 'video/webm'],
        'pdf' => ['pdf' => 'application/pdf'],
    ];

    private static function fail(string $message): never { throw new \InvalidArgumentException($message); }
    public static function valid_slug(string $slug): bool {
        return strlen($slug) <= 64 && preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/D', $slug) === 1;
    }
    private static function valid_text(mixed $value, int $maxbytes, bool $allowempty = false): bool {
        return is_string($value) && mb_check_encoding($value, 'UTF-8') &&
            strlen($value) <= $maxbytes && ($allowempty || trim($value) !== '') &&
            !preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $value);
    }
    private static function assert_text(mixed $value, int $maxbytes, string $label, bool $empty = false): void {
        if (!self::valid_text($value, $maxbytes, $empty)) { self::fail('Texte invalide : ' . $label); }
    }
    private static function mime_aliases(string $mime): array {
        return match ($mime) {
            'audio/mpeg' => ['audio/mpeg', 'audio/mp3'],
            'audio/mp4' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
            'audio/ogg' => ['audio/ogg', 'application/ogg'],
            default => [$mime],
        };
    }
    private static function media_path(string $area, ?int $index, string $name): string {
        if (!array_key_exists($area, self::AREALIMITS) ||
            !preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,119}\z/D', $name) ||
            str_contains($name, '..')) { self::fail('Nom/zone média non autorisé'); }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::EXTENSIONS[$area][$extension])) { self::fail('Extension média non autorisée'); }
        if ($area === 'pageimage') {
            if ($index === null || $index < 0 || $index > 99) { self::fail('Page illustration hors limites'); }
            return 'media/pageimage/p' . $index . '/' . $name;
        }
        if ($index !== null) { self::fail('Numéro de page inattendu sur média'); }
        return 'media/' . $area . '/' . $name;
    }
    private static function expected_mime(string $area, string $name): string {
        return self::EXTENSIONS[$area][strtolower(pathinfo($name, PATHINFO_EXTENSION))];
    }
    private static function json(array $data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
    private static function sha256_stored(\stored_file $file): string {
        $stream = $file->get_content_file_handle();
        try {
            $ctx = hash_init('sha256');
            if (hash_update_stream($ctx, $stream) === false) { self::fail('Lecture média impossible'); }
            return hash_final($ctx);
        } finally { fclose($stream); }
    }

    /** Validate pure manifest before touching any Moodle tables or files. */
    public static function validate_manifest(array $manifest): array {
        if (($manifest['schema'] ?? null) !== self::SCHEMA ||
            !isset($manifest['story'], $manifest['pages'], $manifest['media']) ||
            !is_array($manifest['story']) || !is_array($manifest['pages']) ||
            !array_is_list($manifest['pages']) || !is_array($manifest['media']) ||
            !array_is_list($manifest['media']) || count($manifest['pages']) < 1 ||
            count($manifest['pages']) > 100 || count($manifest['media']) > 250) {
            self::fail('Schéma ZIP d’histoire non reconnu ou incomplet');
        }
        $story = $manifest['story'];
        if (!isset($story['slug']) || !is_string($story['slug']) || !self::valid_slug($story['slug'])) {
            self::fail('Slug source invalide');
        }
        self::assert_text($story['title'] ?? null, 191 * 4, 'titre');
        self::assert_text($story['subtitle'] ?? null, 255 * 4, 'sous-titre', true);
        if (mb_strlen($story['title'], 'UTF-8') > 191 ||
            mb_strlen($story['subtitle'], 'UTF-8') > 255) {
            self::fail('Titre ou sous-titre trop long pour Moodle');
        }
        $hasimage = [];
        foreach ($manifest['pages'] as $i => $page) {
            if (!is_array($page) || ($page['pageindex'] ?? null) !== $i ||
                !in_array($page['kind'] ?? null, ['cover', 'text', 'image', 'mixed', 'back'], true) ||
                !array_key_exists('imagefile', $page) ||
                !isset($page['paragraphs']) || !is_array($page['paragraphs']) ||
                !array_is_list($page['paragraphs']) || count($page['paragraphs']) < 1 ||
                count($page['paragraphs']) > 8) {
                self::fail('Page invalide n° ' . ($i + 1));
            }
            self::assert_text($page['title'] ?? null, 191 * 4, 'titre de page');
            if (mb_strlen($page['title'], 'UTF-8') > 191 ||
                mb_strlen($page['imagealt'] ?? '', 'UTF-8') > 255) {
                self::fail('Titre ou description de page trop long');
            }
            self::assert_text($page['imagealt'] ?? null, 255 * 4, 'description accessible', true);
            foreach ($page['paragraphs'] as $text) { self::assert_text($text, 5000, 'paragraphe'); }
            if ($page['imagefile'] !== null &&
                (!is_string($page['imagefile']) ||
                    !in_array($page['kind'], ['cover','image','mixed','back'], true))) {
                self::fail('Illustration non conforme');
            }
            if ($i === 0 && $page['kind'] !== 'cover') { self::fail('Première page : couverture obligatoire'); }
            if ($i !== 0 && $page['kind'] === 'cover') { self::fail('Une seule couverture est autorisée'); }
            if ($page['kind'] === 'back' && $i !== count($manifest['pages']) - 1) {
                self::fail('Page de fin mal placée');
            }
            if ($page['imagefile'] !== null) {
                $hasimage[$i] = self::media_path('pageimage', $i, $page['imagefile']);
            }
        }
        $paths = []; $total = 0; $foundimage = [];
        foreach ($manifest['media'] as $media) {
            if (!is_array($media) || !isset($media['area'], $media['filename'], $media['path'],
                $media['mimetype'], $media['sha1'], $media['sha256'], $media['bytes'])) {
                self::fail('Descripteur média incomplet');
            }
            $area = $media['area']; $filename = $media['filename'];
            if (!is_string($area) || !is_string($filename) ||
                !array_key_exists($area, self::AREALIMITS)) { self::fail('Zone média inconnue'); }
            $index = $media['pageindex'] ?? null;
            if ($index !== null && !is_int($index)) { self::fail('Index média invalide'); }
            $expected = self::media_path($area, $index, $filename);
            if ($media['path'] !== $expected || isset($paths[$expected]) ||
                $media['mimetype'] !== self::expected_mime($area, $filename) ||
                !is_int($media['bytes']) || $media['bytes'] <= 0 ||
                $media['bytes'] > self::AREALIMITS[$area] ||
                !is_string($media['sha1']) || !preg_match('/\A[a-f0-9]{40}\z/D', $media['sha1']) ||
                !is_string($media['sha256']) || !preg_match('/\A[a-f0-9]{64}\z/D', $media['sha256'])) {
                self::fail('Chemin, type ou empreinte d’un média incorrect');
            }
            $paths[$expected] = true;
            $total += $media['bytes'];
            if ($total > self::MAXTOTAL) { self::fail('Archive trop volumineuse après décompression'); }
            if ($area === 'pageimage') {
                if (!isset($hasimage[$index]) || $hasimage[$index] !== $expected || isset($foundimage[$index])) {
                    self::fail('Illustration orpheline ou en double');
                }
                $foundimage[$index] = true;
            }
        }
        if (count($foundimage) !== count($hasimage)) { self::fail('Illustration référencée absente'); }
        return ['pages' => count($manifest['pages']), 'media' => count($manifest['media']), 'bytes' => $total];
    }

    /** Export returns a request-scoped ZIP path. The controller streams it to authorized CRM users. */
    public static function export_story(int $bookid): array {
        global $DB;
        $book = ReaderEditorialService::book($bookid);
        $slug = (string)$book->slug;
        if (!self::valid_slug($slug)) { self::fail('Slug du livre source invalide'); }
        $pages = array_values($DB->get_records('local_cr_page', ['bookid' => $bookid], 'pageindex ASC', '*', 0, 101));
        $payloadpages = [];
        foreach ($pages as $pos => $page) {
            $paras = json_decode((string)$page->paragraphsjson, true, 12, JSON_THROW_ON_ERROR);
            $payloadpages[] = ['pageindex' => (int)$page->pageindex, 'kind' => (string)$page->kind,
                'title' => (string)$page->title, 'paragraphs' => $paras,
                'imagefile' => $page->imagefile === null || $page->imagefile === '' ? null : (string)$page->imagefile,
                'imagealt' => (string)($page->imagealt ?? '')];
        }
        $files = []; $payloadmedia = [];
        $fs = get_file_storage(); $ctx = \context_system::instance();
        foreach (array_keys(self::AREALIMITS) as $area) {
            foreach ($fs->get_area_files($ctx->id, self::COMPONENT, $area, $bookid, 'id ASC', false) as $file) {
                if ($file->is_directory()) { continue; }
                $index = null;
                if ($area === 'pageimage') {
                    if (!preg_match('#\A/p(0|[1-9][0-9]{0,2})/\z#D', $file->get_filepath(), $matches)) {
                        self::fail('Chemin d’illustration source non conforme');
                    }
                    $index = (int)$matches[1];
                } else if ($file->get_filepath() !== '/') {
                    self::fail('Chemin de média source non conforme');
                }
                $path = self::media_path($area, $index, $file->get_filename());
                $mime = self::expected_mime($area, $file->get_filename());
                if (!in_array($file->get_mimetype(), self::mime_aliases($mime), true)) {
                    self::fail('MIME source non conforme');
                }
                if (isset($files[$path])) { self::fail('Fichier média dupliqué dans la source'); }
                $files[$path] = $file;
                $payloadmedia[] = ['area' => $area, 'pageindex' => $index,
                    'filename' => $file->get_filename(), 'path' => $path, 'mimetype' => $mime,
                    'bytes' => (int)$file->get_filesize(), 'sha1' => (string)$file->get_contenthash(),
                    'sha256' => self::sha256_stored($file)];
            }
        }
        $manifest = ['schema' => self::SCHEMA, 'source_environment' => 'dev',
            'story' => ['slug' => $slug, 'title' => (string)$book->title,
                'subtitle' => (string)$book->subtitle],
            'pages' => $payloadpages, 'media' => $payloadmedia];
        self::validate_manifest($manifest);
        // Moodle archive_writer::get_file_writer() expects a filename only.
        // Its zip_writer creates the file inside make_request_directory() itself.
        $filename = 'campusfr-histoire-' . $slug . '.zip';
        $writer = \core_files\archive_writer::get_file_writer($filename, \core_files\archive_writer::ZIP_WRITER);
        $writer->add_file_from_string('manifest.json', self::json($manifest));
        foreach ($files as $path => $file) { $writer->add_file_from_stored_file($path, $file); }
        $writer->finish();
        $output = $writer->get_path_to_zip();
        if (!is_file($output) || filesize($output) <= 0 || filesize($output) > self::MAXARCHIVE) {
            self::fail('ZIP généré invalide ou trop volumineux');
        }
        return ['path' => $output, 'filename' => basename($output),
            'sha256' => hash_file('sha256', $output), 'pages' => count($payloadpages),
            'media' => count($payloadmedia)];
    }

    /** Validation without extraction: reject extra entries, links, duplicates, bombs and any corrupted media. */
    public static function inspect_zip(string $path): array {
        if (!class_exists(\ZipArchive::class)) { self::fail('Extension PHP ZIP indisponible sur le serveur'); }
        if (!is_file($path) || is_link($path) || filesize($path) < 50 || filesize($path) > self::MAXARCHIVE) {
            self::fail('Fichier ZIP absent ou trop volumineux');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) { self::fail('ZIP illisible'); }
        try {
            if ($zip->numFiles < 1 || $zip->numFiles > 251) { self::fail('Nombre d’entrées ZIP incorrect'); }
            $manifeststat = $zip->statName('manifest.json');
            if (!$manifeststat || ($manifeststat['size'] ?? -1) < 1 ||
                ($manifeststat['size'] ?? -1) > 524288) {
                self::fail('Manifeste absent ou volumineux');
            }
            $raw = $zip->getFromName('manifest.json');
            if (!is_string($raw) || strlen($raw) > 524288) { self::fail('Manifeste illisible'); }
            $manifest = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($manifest)) { self::fail('Manifeste incorrect'); }
            $stats = self::validate_manifest($manifest);
            $allowed = ['manifest.json' => true];
            foreach ($manifest['media'] as $media) { $allowed[$media['path']] = true; }
            if ($zip->numFiles !== count($allowed)) { self::fail('ZIP contenant des entrées supplémentaires'); }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat || !is_string($stat['name']) || !isset($allowed[$stat['name']])) {
                    self::fail('Chemin ZIP non autorisé');
                }
                unset($allowed[$stat['name']]);
                if (($stat['encryption_method'] ?? 0) !== 0 ||
                    ($stat['size'] ?? -1) > self::MAXTOTAL || ($stat['size'] ?? -1) < 0) {
                    self::fail('Entrée ZIP chiffrée ou hors limite');
                }
                if (method_exists($zip, 'getExternalAttributesIndex')) {
                    $opsys = 0; $attr = 0;
                    if ($zip->getExternalAttributesIndex($i, $opsys, $attr) && (($attr >> 16) & 0170000) === 0120000) {
                        self::fail('Lien symbolique ZIP interdit');
                    }
                }
            }
            if ($allowed) { self::fail('Entrée ZIP attendue manquante ou répétée'); }
            $metabytes = ['manifest.json' => strlen($raw)];
            foreach ($manifest['media'] as $media) { $metabytes[$media['path']] = $media['bytes']; }
            foreach ($metabytes as $name => $bytes) {
                $stat = $zip->statName($name);
                if (!$stat || (int)$stat['size'] !== $bytes) { self::fail('Taille ZIP incorrecte : ' . $name); }
            }
            foreach ($manifest['media'] as $media) {
                $stream = $zip->getStream($media['path']);
                if (!is_resource($stream)) { self::fail('Média ZIP illisible'); }
                try {
                    $sha1 = hash_init('sha1'); $sha256 = hash_init('sha256'); $size = 0;
                    while (!feof($stream)) {
                        $chunk = fread($stream, 1048576);
                        if ($chunk === false) { self::fail('Lecture ZIP impossible'); }
                        if ($chunk === '') { if (feof($stream)) { break; } self::fail('Lecture ZIP interrompue'); }
                        $size += strlen($chunk);
                        if ($size > $media['bytes']) { self::fail('ZIP média trop volumineux'); }
                        hash_update($sha1, $chunk); hash_update($sha256, $chunk);
                    }
                    if ($size !== $media['bytes'] || hash_final($sha1) !== $media['sha1'] ||
                        hash_final($sha256) !== $media['sha256']) { self::fail('Média altéré : ' . $media['path']); }
                } finally { fclose($stream); }
            }
            $stats['title'] = $manifest['story']['title'];
            $stats['slug'] = $manifest['story']['slug'];
            $stats['zipsha256'] = hash_file('sha256', $path);
            $stats['manifest'] = $manifest;
            return $stats;
        } finally { $zip->close(); }
    }

    public static function assert_qa_destination(int $collectionid, string $newslug): \stdClass {
        global $DB;
        if (!self::valid_slug($newslug)) { self::fail('Slug de destination : lettres minuscules, chiffres et tirets uniquement'); }
        $collection = ReaderEditorialService::collection($collectionid);
        if ((int)$collection->published !== 0 ||
            !preg_match('/(?:^|-)(qa|test)(?:-|$)/', (string)$collection->slug)) {
            self::fail('Import initial limité à une collection QA en brouillon');
        }
        if ($DB->record_exists('local_cr_book', ['collectionid' => $collectionid, 'slug' => $newslug])) {
            self::fail('Une histoire avec ce slug existe déjà : aucun écrasement autorisé');
        }
        return $collection;
    }

    /**
     * Store prevalidated, bounded media through the normal Moodle File API.
     * String storage for small files mirrors D2.13.3.1; larger files keep streaming.
     * Both the return value and an independent File API lookup MUST match,
     * including a second reading of the stored bytes. Never bypass File API hooks.
     * The caller owns database transactions and compensating media cleanup.
     */
    public static function store_verified_media(array $record, string $source,
        string $sha1, int $size, string $mime): void {
        if (!is_file($source) || is_link($source) || $size < 1 || $size > 209715200 ||
            !preg_match('/\A[a-f0-9]{40}\z/D', $sha1) ||
            !preg_match('#\A[a-z0-9][a-z0-9+./-]*\z#D', $mime)) {
            self::fail('Média import : source ou contrat invalide');
        }
        if ((int)filesize($source) !== $size ||
            !hash_equals($sha1, (string)hash_file('sha1', $source))) {
            self::fail('Média import : source différente du manifeste');
        }
        foreach (['contextid', 'component', 'filearea', 'itemid', 'filepath', 'filename', 'mimetype'] as $key) {
            if (!array_key_exists($key, $record)) { self::fail('Média import : coordonnées absentes'); }
        }
        if ($record['mimetype'] !== $mime || (int)$record['itemid'] < 1 ||
            (int)$record['contextid'] < 1) { self::fail('Média import : métadonnées invalides'); }
        $storage = get_file_storage();
        $coords = [(int)$record['contextid'], (string)$record['component'],
            (string)$record['filearea'], (int)$record['itemid'],
            (string)$record['filepath'], (string)$record['filename']];
        if ($storage->get_file(...$coords) !== false) {
            self::fail('Média import : destination déjà occupée');
        }
        if ($size <= 8388608) {
            $content = file_get_contents($source);
            if (!is_string($content) || strlen($content) !== $size ||
                !hash_equals($sha1, sha1($content))) {
                self::fail('Média import : octets avant écriture divergents');
            }
            $created = $storage->create_file_from_string($record, $content);
            unset($content);
        } else {
            // Do not load large audio/video/PDF blobs into PHP memory.
            $created = $storage->create_file_from_pathname($record, $source);
        }
        $reloaded = $storage->get_file(...$coords);
        foreach (['retour' => $created, 'relecture' => $reloaded] as $stage => $file) {
            if (!($file instanceof \stored_file)) {
                self::fail('Média import : ' . $stage . ' absent');
            }
            if ($file->is_directory() || $file->is_external_file() ||
                (int)$file->get_status() !== 0) {
                self::fail('Média import : ' . $stage . ' statut/référence incorrect');
            }
            if ((int)$file->get_contextid() !== $coords[0] ||
                (string)$file->get_component() !== $coords[1] ||
                (string)$file->get_filearea() !== $coords[2] ||
                (int)$file->get_itemid() !== $coords[3] ||
                (string)$file->get_filepath() !== $coords[4] ||
                (string)$file->get_filename() !== $coords[5]) {
                self::fail('Média import : ' . $stage . ' coordonnées incorrectes');
            }
            if (!hash_equals($sha1, (string)$file->get_contenthash())) {
                self::fail('Média import : ' . $stage . ' SHA1 divergent');
            }
            if ((string)$file->get_filesize() !== (string)$size) {
                self::fail('Média import : ' . $stage . ' taille divergente');
            }
            if ((string)$file->get_mimetype() !== $mime) {
                self::fail('Média import : ' . $stage . ' MIME divergent');
            }
        }
        $stream = $reloaded->get_content_file_handle();
        if (!is_resource($stream)) { self::fail('Média import : relecture impossible'); }
        try {
            $hash = hash_init('sha1');
            $nbytes = hash_update_stream($hash, $stream);
            if (!is_int($nbytes) || $nbytes !== $size ||
                !hash_equals($sha1, hash_final($hash))) {
                self::fail('Média import : octets stockés divergents');
            }
        } finally { fclose($stream); }
    }

    /** Create a fresh draft only, no slots, entitlements, progress, revisions or publication. */
    public static function import_qa(string $path, int $collectionid, string $newslug, int $actor,
        string $expectedzipsha256): int {
        global $DB;
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $expectedzipsha256) ||
            !hash_equals($expectedzipsha256, hash_file('sha256', $path))) {
            self::fail('Archive modifiée depuis la prévisualisation');
        }
        self::assert_qa_destination($collectionid, $newslug);
        $data = self::inspect_zip($path);
        $manifest = $data['manifest'];
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) { self::fail('Réouverture ZIP impossible'); }
        $tx = $DB->start_delegated_transaction();
        $id = 0;
        try {
            self::assert_qa_destination($collectionid, $newslug);
            $now = time();
            $id = (int)$DB->insert_record('local_cr_book', (object)[
                'collectionid' => $collectionid, 'slug' => $newslug,
                'title' => $manifest['story']['title'], 'subtitle' => $manifest['story']['subtitle'],
                'published' => 0, 'timecreated' => $now, 'timemodified' => $now,
            ]);
            foreach ($manifest['pages'] as $page) {
                $DB->insert_record('local_cr_page', (object)[
                    'bookid' => $id, 'pageindex' => $page['pageindex'], 'kind' => $page['kind'],
                    'title' => $page['title'], 'paragraphsjson' => json_encode($page['paragraphs'],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    'imagefile' => $page['imagefile'], 'imagealt' => $page['imagealt'],
                    'timecreated' => $now, 'timemodified' => $now,
                ]);
            }
            $fs = get_file_storage(); $ctx = \context_system::instance();
            foreach ($manifest['media'] as $media) {
                $temp = tempnam(make_request_directory(), 'cfr-media-');
                if ($temp === false) { self::fail('Dossier temporaire indisponible'); }
                try {
                    $stream = $zip->getStream($media['path']);
                    if (!is_resource($stream)) { self::fail('Média à importer illisible'); }
                    $output = fopen($temp, 'wb');
                    if ($output === false) { fclose($stream); self::fail('Écriture temporaire impossible'); }
                    try {
                        $bytes = stream_copy_to_stream($stream, $output, $media['bytes'] + 1);
                        if ($bytes !== $media['bytes']) { self::fail('Copie média incomplète'); }
                    } finally { fclose($stream); fclose($output); }
                    if (hash_file('sha256', $temp) !== $media['sha256'] ||
                        hash_file('sha1', $temp) !== $media['sha1']) {
                        self::fail('Média différent après extraction');
                    }
                    $realmime = (new \finfo(FILEINFO_MIME_TYPE))->file($temp);
                    if (!in_array($realmime, self::mime_aliases($media['mimetype']), true)) {
                        self::fail('MIME réel incorrect : ' . $media['filename']);
                    }
                    if (in_array($media['area'], ['cover', 'pageimage'], true)) {
                        $size = @getimagesize($temp);
                        if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 35000000) {
                            self::fail('Image invalide : ' . $media['filename']);
                        }
                    }
                    $record = ['contextid' => $ctx->id, 'component' => self::COMPONENT,
                        'filearea' => $media['area'], 'itemid' => $id,
                        'filepath' => $media['area'] === 'pageimage' ? '/p' . $media['pageindex'] . '/' : '/',
                        'filename' => $media['filename'], 'userid' => $actor,
                        'mimetype' => $media['mimetype'], 'license' => 'allrightsreserved'];
                    // D2.17.1.1: small media use the Moodle File API string path, as in our QA seed.
                    // All media, including larger stream-imported files, are independently re-read.
                    self::store_verified_media($record, $temp, (string)$media['sha1'],
                        (int)$media['bytes'], (string)$media['mimetype']);
                } finally { @unlink($temp); }
            }
            $tx->allow_commit();
            error_log('[CampusFR D2.5 transfer] import_qa actor=' . $actor . ' collection=' . $collectionid .
                ' book=' . $id . ' pages=' . $data['pages'] . ' media=' . $data['media']);
            return $id;
        } catch (\Throwable $e) {
            // Moodle File API is not guaranteed to roll back binary writes with DB transactions.
            if ($id > 0) {
                $storage = get_file_storage();
                $contextid = (int)\context_system::instance()->id;
                foreach (array_keys(self::AREALIMITS) as $area) {
                    try { $storage->delete_area_files($contextid, self::COMPONENT, $area, $id); }
                    catch (\Throwable $ignored) { /* Keep original exception. */ }
                }
            }
            error_log('[CampusFR D2.5 transfer] import_qa FAILED actor=' . $actor . ' collection=' .
                $collectionid . ' new_book=' . $id . ' exception=' . get_class($e));
            $tx->rollback($e);
            throw $e;
        } finally { $zip->close(); }
    }
}
