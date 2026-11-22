<?php
// CampusFR D2.6 — Private portable editorial bundles (DEV QA intake only).
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderLibraryBundleService {
    private const FORMAT = 'campusfr.library.bundle.v1';
    private const MAXZIP = 2147483648;   // 2 GiB; PHP upload limits may be smaller.
    private const MAXBYTES = 3221225472; // 3 GiB extracted across all entries.
    private const MAXSTORIES = 100;
    private const MAXREVISIONS = 30;
    private const MAXENTRIES = 1400;
    private const AREA = 'anthrev';

    private static function error(string $s): never { throw new \InvalidArgumentException($s); }
    private static function slug(mixed $s): bool {
        return is_string($s) && ReaderStoryTransferService::valid_slug($s);
    }
    private static function text(mixed $s, int $max, bool $optional = false): bool {
        return is_string($s) && mb_check_encoding($s, 'UTF-8') && strlen($s) <= $max * 4 &&
            mb_strlen($s, 'UTF-8') <= $max && ($optional || trim($s) !== '') &&
            !preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $s);
    }
    private static function sha(mixed $v): bool { return is_string($v) && preg_match('/\A[a-f0-9]{64}\z/D', $v) === 1; }
    private static function json(array $a): string {
        return json_encode($a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
    private static function assert_frozen_descriptor(array $media): void {
        $types = [
            'png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'], 'mp3' => ['audio/mpeg', 'audio/mp3'],
            'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
            'ogg' => ['audio/ogg', 'application/ogg'],
            'mp4' => ['video/mp4'], 'webm' => ['video/webm'], 'pdf' => ['application/pdf'],
        ];
        $area = (string)($media['area'] ?? '');
        $ext = strtolower(pathinfo((string)($media['filename'] ?? ''), PATHINFO_EXTENSION));
        $allowedarea = match ($area) {
            'cover', 'pageimage' => ['png','jpg','jpeg','webp'],
            'narration' => ['mp3','m4a','ogg'], 'video' => ['mp4','webm'], 'pdf' => ['pdf'],
            default => [],
        };
        if (!in_array($ext, $allowedarea, true) ||
            !in_array($media['mimetype'] ?? null, $types[$ext] ?? [], true)) {
            self::error('Type/extension du média figé non autorisé');
        }
    }
    private static function assert_frozen_bytes(string $temp, array $media): void {
        self::assert_frozen_descriptor($media);
        if (filesize($temp) !== (int)$media['size'] ||
            !hash_equals((string)$media['sha1'], hash_file('sha1', $temp))) {
            self::error('Média figé altéré');
        }
        $actual = (new \finfo(FILEINFO_MIME_TYPE))->file($temp);
        $aliases = match ($media['mimetype']) {
            'audio/mpeg', 'audio/mp3' => ['audio/mpeg', 'audio/mp3'],
            'audio/mp4', 'audio/x-m4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
            'audio/ogg', 'application/ogg' => ['audio/ogg', 'application/ogg'],
            default => [$media['mimetype']],
        };
        if (!in_array($actual, $aliases, true)) { self::error('MIME réel du média figé incorrect'); }
        if (in_array($media['area'], ['cover','pageimage'], true)) {
            $size = @getimagesize($temp);
            if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 35000000) {
                self::error('Illustration figée invalide');
            }
        }
    }
    private static function zip_path(string $slug, string $kind): string {
        return make_request_directory() . '/campusfr-' . $kind . '-' . $slug . '-' . bin2hex(random_bytes(4)) . '.zip';
    }
    private static function sha256_file(\stored_file $f): string {
        $handle = $f->get_content_file_handle();
        try { $ctx = hash_init('sha256'); hash_update_stream($ctx, $handle); return hash_final($ctx); }
        finally { fclose($handle); }
    }
    private static function add_stored_file(\ZipArchive $zip, \stored_file $file, string $entry, array &$temps): void {
        $temp = tempnam(make_request_directory(), 'd26-');
        if ($temp === false) { self::error('Fichier temporaire indisponible'); }
        $source = $file->get_content_file_handle();
        $dest = fopen($temp, 'wb');
        if (!$dest) { fclose($source); @unlink($temp); self::error('Fichier temporaire inaccessible'); }
        try {
            if (stream_copy_to_stream($source, $dest) !== (int)$file->get_filesize()) {
                self::error('Copie média incomplète');
            }
        } finally { fclose($source); fclose($dest); }
        if (!$zip->addFile($temp, $entry)) { @unlink($temp); self::error('Erreur d’ajout du média au ZIP'); }
        $temps[] = $temp;
    }

    /** Build an export from a recueil (chapters + historical private revisions) or calendar (active slots). */
    public static function export(string $kind, int $id): array {
        global $DB;
        if (!in_array($kind, ['anthology', 'calendar'], true)) { self::error('Type d’export inconnu'); }
        if ($kind === 'anthology') {
            $object = ReaderEditorialAnthologyService::anthology($id);
            $chapters = ReaderEditorialAnthologyService::chapters($id);
            if (!$chapters) { self::error('Ce recueil n’a pas de chapitres'); }
            $manifest = ['schema' => self::FORMAT, 'kind' => 'anthology',
                'object' => ['slug' => (string)$object->slug, 'title' => (string)$object->title,
                    'subtitle' => (string)$object->subtitle],
                'stories' => [], 'chapters' => [], 'revisions' => []];
        } else {
            $object = ReaderEditorialService::collection($id);
            $count = ReaderEditorialCalendarService::count($object);
            if ($count < 1 || $count > 31) { self::error('Calendrier non configuré'); }
            $slots = ReaderEditorialCalendarService::slots($id);
            $manifest = ['schema' => self::FORMAT, 'kind' => 'calendar',
                'object' => ['slug' => (string)$object->slug, 'title' => (string)$object->title,
                    'timezone' => (string)$object->timezone, 'presentationmode' => (string)$object->presentationmode,
                    'libraryafter' => (int)$object->libraryafter, 'slotcount' => $count],
                'stories' => [], 'slots' => []];
        }
        $ids = [];
        $register = static function(int $bookid) use (&$ids): int {
            if ($bookid < 1) { throw new \InvalidArgumentException('Histoire source invalide'); }
            if (!isset($ids[$bookid])) {
                if (count($ids) >= self::MAXSTORIES) { throw new \InvalidArgumentException('Trop d’histoires'); }
                $ids[$bookid] = count($ids) + 1;
            }
            return $ids[$bookid];
        };
        $revisions = [];
        if ($kind === 'anthology') {
            foreach ($chapters as $c) { $manifest['chapters'][] = $register((int)$c->bookid); }
            foreach (array_reverse(ReaderAnthologyRevisionService::list_for($id)) as $r) {
                if (count($revisions) >= self::MAXREVISIONS) { self::error('Plus de 30 versions : export à segmenter'); }
                $record = ReaderAnthologyRevisionService::get($id, (int)$r->id);
                $snapshot = ReaderAnthologySnapshotContract::decode((string)$record->snapshotjson, (string)$record->digest);
                foreach ($snapshot['chapters'] as &$chapter) {
                    $chapter['bookid'] = $register((int)$chapter['bookid']);
                }
                unset($chapter);
                $portable = ReaderAnthologySnapshotContract::encode($snapshot);
                $revisions[] = ['record' => $record, 'snapshot' => $snapshot, 'json' => $portable];
            }
        } else {
            for ($day = 1; $day <= $count; $day++) {
                if (!isset($slots[$day])) { self::error('Case active manquante : ' . $day); }
                $slot = $slots[$day];
                $manifest['slots'][] = ['position' => $day, 'unlockat' => (int)$slot->unlockat,
                    'teaser' => (string)$slot->teaser, 'layoutsize' => (string)$slot->layoutsize,
                    'story' => empty($slot->bookid) ? null : $register((int)$slot->bookid)];
            }
            // Also transport unassigned canonical stories in the collection for editorial completeness.
            foreach (ReaderEditorialService::books($id) as $book) { $register((int)$book->id); }
        }
        $output = self::zip_path((string)$object->slug, $kind);
        $zip = new \ZipArchive();
        if ($zip->open($output, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) { self::error('Création ZIP impossible'); }
        $temps = [];
        try {
            foreach ($ids as $bookid => $ref) {
                $info = ReaderStoryTransferService::export_story((int)$bookid);
                $story = ReaderEditorialService::book((int)$bookid);
                $entry = sprintf('stories/s%04d.zip', $ref);
                $manifest['stories'][] = ['ref' => $ref, 'slug' => (string)$story->slug, 'path' => $entry,
                    'sha256' => $info['sha256'], 'bytes' => (int)filesize($info['path'])];
                if (!$zip->addFile($info['path'], $entry)) { self::error('Impossible d’ajouter une histoire au ZIP'); }
                $temps[] = $info['path'];
            }
            foreach ($revisions as $r) {
                $rec = $r['record']; $index = count($manifest['revisions']) + 1;
                $snapshotpath = sprintf('revisions/r%03d.json', $index);
                $entry = ['ordinal' => $index, 'snapshot' => $snapshotpath,
                    'sha256' => hash('sha256', $r['json']), 'source_digest' => (string)$rec->digest,
                    'source_state' => (string)$rec->state, 'media' => []];
                if (!$zip->addFromString($snapshotpath, $r['json'])) { self::error('Ajout de la version impossible'); }
                foreach ($r['snapshot']['media'] as $i => $m) {
                    $file = ReaderAnthologyRevisionService::media($id, (int)$rec->id, $i);
                    $path = sprintf('revisions/r%03d/m%05d.bin', $index, $i);
                    $entry['media'][] = ['index' => $i, 'path' => $path,
                        'sha256' => self::sha256_file($file), 'bytes' => (int)$file->get_filesize()];
                    self::add_stored_file($zip, $file, $path, $temps);
                }
                $manifest['revisions'][] = $entry;
            }
            self::validate_manifest($manifest);
            if (!$zip->addFromString('manifest.json', self::json($manifest))) { self::error('Manifeste ZIP impossible'); }
        } finally {
            $zip->close();
            foreach ($temps as $temp) { @unlink($temp); }
        }
        if (!is_file($output) || filesize($output) < 100 || filesize($output) > self::MAXZIP) {
            @unlink($output); self::error('ZIP final absent ou trop volumineux');
        }
        return ['path' => $output, 'filename' => basename($output), 'sha256' => hash_file('sha256', $output),
            'stories' => count($ids), 'revisions' => count($revisions)];
    }

    /** Pure manifest contract. No Moodle calls; also exercised offline in the package. */
    public static function validate_manifest(array $m): void {
        if (($m['schema'] ?? '') !== self::FORMAT || !in_array($m['kind'] ?? null, ['anthology','calendar'], true)
            || !isset($m['object'], $m['stories']) || !is_array($m['object']) || !is_array($m['stories'])
            || !array_is_list($m['stories']) || count($m['stories']) < 1 || count($m['stories']) > self::MAXSTORIES) {
            self::error('Manifeste de bibliothèque non reconnu');
        }
        $o = $m['object'];
        if (!self::slug($o['slug'] ?? null) || !self::text($o['title'] ?? null, 191)) { self::error('Métadonnées invalides'); }
        if ($m['kind'] === 'anthology' && !self::text($o['subtitle'] ?? null, 255, true)) {
            self::error('Sous-titre invalide');
        }
        $allowed = ['manifest.json' => true]; $refs = []; $slugs = [];
        foreach ($m['stories'] as $i => $story) {
            $ref = $i + 1; $path = sprintf('stories/s%04d.zip', $ref);
            if (!is_array($story) || ($story['ref'] ?? null) !== $ref || ($story['path'] ?? null) !== $path ||
                !self::slug($story['slug'] ?? null) || !self::sha($story['sha256'] ?? null) ||
                !is_int($story['bytes'] ?? null) || $story['bytes'] < 50 || $story['bytes'] > 268435456 ||
                isset($allowed[$path]) || isset($slugs[$story['slug']])) { self::error('Histoire portable invalide'); }
            $allowed[$path] = true; $refs[$ref] = true; $slugs[$story['slug']] = true;
        }
        if ($m['kind'] === 'anthology') {
            if (!isset($m['chapters'], $m['revisions']) || !is_array($m['chapters']) || !array_is_list($m['chapters']) ||
                count($m['chapters']) < 1 || count($m['chapters']) > self::MAXSTORIES ||
                !is_array($m['revisions']) || !array_is_list($m['revisions']) ||
                count($m['revisions']) > self::MAXREVISIONS) { self::error('Chapitres ou versions invalides'); }
            $used = [];
            foreach ($m['chapters'] as $ref) {
                if (!is_int($ref) || !isset($refs[$ref]) || isset($used[$ref])) { self::error('Référence de chapitre incorrecte'); }
                $used[$ref] = true;
            }
            foreach ($m['revisions'] as $i => $rev) {
                $num = $i + 1; $path = sprintf('revisions/r%03d.json', $num);
                if (!is_array($rev) || ($rev['ordinal'] ?? null) !== $num || ($rev['snapshot'] ?? null) !== $path ||
                    !self::sha($rev['sha256'] ?? null) || !self::sha($rev['source_digest'] ?? null) ||
                    !in_array($rev['source_state'] ?? null, ['review','approved'], true) ||
                    !isset($rev['media']) || !is_array($rev['media']) || !array_is_list($rev['media']) ||
                    count($rev['media']) > 1200) { self::error('Version éditoriale incorrecte'); }
                $allowed[$path] = true;
                foreach ($rev['media'] as $j => $media) {
                    $mediaPath = sprintf('revisions/r%03d/m%05d.bin', $num, $j);
                    if (!is_array($media) || ($media['index'] ?? null) !== $j || ($media['path'] ?? null) !== $mediaPath ||
                        !self::sha($media['sha256'] ?? null) || !is_int($media['bytes'] ?? null) ||
                        $media['bytes'] < 1 || $media['bytes'] > 209715200) { self::error('Média de version incorrect'); }
                    $allowed[$mediaPath] = true;
                }
            }
        } else {
            $count = $o['slotcount'] ?? 0;
            if (($o['timezone'] ?? null) !== 'Europe/Paris' || !in_array($o['presentationmode'] ?? null, ['auto','advent','library'], true) ||
                !is_int($count) || $count < 1 || $count > 31 || !is_int($o['libraryafter'] ?? null) ||
                !isset($m['slots']) || !is_array($m['slots']) || !array_is_list($m['slots']) || count($m['slots']) !== $count) {
                self::error('Configuration calendrier incorrecte');
            }
            $prev = 0; $used = [];
            foreach ($m['slots'] as $i => $slot) {
                if (!is_array($slot) || ($slot['position'] ?? null) !== $i + 1 ||
                    !is_int($slot['unlockat'] ?? null) || $slot['unlockat'] <= $prev ||
                    !self::text($slot['teaser'] ?? null, 191) ||
                    !in_array($slot['layoutsize'] ?? null, ['normal','wide','tall','feature'], true) ||
                    !array_key_exists('story', $slot) ||
                    ($slot['story'] !== null && (!is_int($slot['story']) || !isset($refs[$slot['story']]) || isset($used[$slot['story']])))) {
                    self::error('Case ou date invalide dans l’archive');
                }
                $prev = $slot['unlockat'];
                if ($slot['story'] !== null) { $used[$slot['story']] = true; }
            }
            if ($o['presentationmode'] === 'auto' && $o['libraryafter'] <= $prev) {
                self::error('Date de passage en bibliothèque incohérente');
            }
        }
        if (count($allowed) > self::MAXENTRIES) { self::error('Nombre d’entrées trop élevé'); }
    }

    /** Validate actual ZIP member content, including nested story ZIPs and frozen revision blobs. */
    public static function inspect_zip(string $path): array {
        if (!is_file($path) || is_link($path) || filesize($path) < 100 || filesize($path) > self::MAXZIP) {
            self::error('Archive absente ou hors limites');
        }
        $z = new \ZipArchive();
        if ($z->open($path, \ZipArchive::RDONLY) !== true) { self::error('Archive illisible'); }
        try {
            $raw = $z->getFromName('manifest.json');
            if (!is_string($raw) || strlen($raw) > 524288) { self::error('Manifeste absent ou trop volumineux'); }
            $m = json_decode($raw, true, 45, JSON_THROW_ON_ERROR);
            if (!is_array($m)) { self::error('Manifeste incorrect'); }
            self::validate_manifest($m);
            $expected = ['manifest.json' => strlen($raw)];
            foreach ($m['stories'] as $s) { $expected[$s['path']] = $s['bytes']; }
            if ($m['kind'] === 'anthology') {
                foreach ($m['revisions'] as $r) {
                    $expected[$r['snapshot']] = null;
                    foreach ($r['media'] as $item) { $expected[$item['path']] = $item['bytes']; }
                }
            }
            if ($z->numFiles !== count($expected)) { self::error('ZIP avec fichiers supplémentaires ou manquants'); }
            $total = 0;
            for ($i = 0; $i < $z->numFiles; $i++) {
                $st = $z->statIndex($i); $name = $st['name'] ?? null;
                if (!is_string($name) || !array_key_exists($name, $expected) ||
                    ($expected[$name] !== null && (int)$st['size'] !== $expected[$name]) ||
                    (int)$st['size'] < 0 || (int)$st['size'] > self::MAXBYTES ||
                    ($st['encryption_method'] ?? 0) !== 0) { self::error('Entrée ZIP incorrecte'); }
                $total += (int)$st['size'];
                if ($total > self::MAXBYTES) { self::error('Archive décompressée trop volumineuse'); }
                $opsys = 0; $attr = 0;
                if ($z->getExternalAttributesIndex($i, $opsys, $attr) && (($attr >> 16) & 0170000) === 0120000) {
                    self::error('Lien symbolique ZIP interdit');
                }
                unset($expected[$name]);
            }
            if ($expected) { self::error('Entrées ZIP manquantes ou répétées'); }
            $revisionpayloads = [];
            if ($m['kind'] === 'anthology') {
                foreach ($m['revisions'] as $r) {
                    $snapraw = $z->getFromName($r['snapshot']);
                    if (!is_string($snapraw) || !hash_equals($r['sha256'], hash('sha256', $snapraw))) {
                        self::error('Version figée altérée');
                    }
                    $snapshot = ReaderAnthologySnapshotContract::decode($snapraw, $r['sha256']);
                    foreach ($snapshot['chapters'] as $ch) {
                        if (!isset($m['stories'][$ch['bookid'] - 1])) { self::error('Version liée à une histoire absente'); }
                    }
                    if (count($r['media']) !== count($snapshot['media'])) { self::error('Médias de version incomplets'); }
                    foreach ($r['media'] as $j => $media) {
                        self::assert_frozen_descriptor($snapshot['media'][$j]);
                        if ($media['bytes'] !== $snapshot['media'][$j]['size'] ||
                            !hash_equals($snapshot['media'][$j]['sha1'], self::hash_entry($z, $media['path'], 'sha1')) ||
                            !hash_equals($media['sha256'], self::hash_entry($z, $media['path'], 'sha256'))) {
                            self::error('Média de version altéré');
                        }
                    }
                    $revisionpayloads[] = $snapshot;
                }
            }
            $storyinfo = [];
            foreach ($m['stories'] as $story) {
                if (!hash_equals($story['sha256'], self::hash_entry($z, $story['path'], 'sha256'))) {
                    self::error('ZIP d’histoire modifié');
                }
                $temp = self::extract_tmp($z, $story['path']);
                try {
                    $info = ReaderStoryTransferService::inspect_zip($temp);
                    if ($info['slug'] !== $story['slug']) { self::error('Slug d’histoire incohérent'); }
                    $storyinfo[] = ['ref' => $story['ref'], 'title' => $info['title'], 'slug' => $info['slug'],
                        'pages' => $info['pages'], 'media' => $info['media']];
                } finally { @unlink($temp); }
            }
            return ['kind' => $m['kind'], 'title' => $m['object']['title'], 'slug' => $m['object']['slug'],
                'stories' => $storyinfo, 'revisions' => count($revisionpayloads), 'slots' => count($m['slots'] ?? []),
                'zipsha256' => hash_file('sha256', $path), 'manifest' => $m];
        } finally { $z->close(); }
    }
    private static function hash_entry(\ZipArchive $z, string $entry, string $algo): string {
        $stream = $z->getStream($entry);
        if (!is_resource($stream)) { self::error('Entrée ZIP inaccessible'); }
        try {
            $ctx = hash_init($algo); $length = 0;
            while (!feof($stream)) {
                $buf = fread($stream, 1048576);
                if ($buf === false || ($buf === '' && !feof($stream))) { self::error('Lecture ZIP incomplète'); }
                if ($buf === '') { break; }
                $length += strlen($buf);
                if ($length > self::MAXBYTES) { self::error('Entrée ZIP excessive'); }
                hash_update($ctx, $buf);
            }
            return hash_final($ctx);
        } finally { fclose($stream); }
    }
    private static function extract_tmp(\ZipArchive $z, string $entry): string {
        $path = tempnam(make_request_directory(), 'cfr-d26-');
        if ($path === false) { self::error('Fichier temporaire indisponible'); }
        $stream = $z->getStream($entry);
        if (!is_resource($stream)) { @unlink($path); self::error('ZIP imbriqué inaccessible'); }
        $out = fopen($path, 'wb');
        if (!$out) { fclose($stream); @unlink($path); self::error('Extraction temporaire impossible'); }
        try { stream_copy_to_stream($stream, $out); }
        finally { fclose($stream); fclose($out); }
        return $path;
    }

    /** Read-only destination plan, generated on demand (never trusted for the final import). */
    public static function plan_qa(array $manifest, string $slug, int $collectionid = 0, int $courseid = 0): array {
        global $DB;
        $problems = [];
        if (!self::slug($slug) || !preg_match('/(?:^|-)(qa|test)(?:-|$)/', $slug)) {
            $problems[] = 'Le nouveau slug doit contenir qa ou test et utiliser seulement des tirets';
        }
        if ($manifest['kind'] === 'anthology') {
            $col = $DB->get_record('local_cr_collection', ['id' => $collectionid]);
            if (!$col || (int)$col->published !== 0 ||
                !preg_match('/(?:^|-)(qa|test)(?:-|$)/', (string)$col->slug)) {
                $problems[] = 'Choisis une collection QA existante en brouillon';
            }
            if ($DB->record_exists('local_cr_anthology', ['slug' => $slug])) {
                $problems[] = 'Un recueil portant ce slug existe déjà';
            }
            foreach ($manifest['stories'] as $story) {
                $newslug = self::new_story_slug($story['slug'], $slug, $story['ref']);
                if ($DB->record_exists('local_cr_book', ['collectionid' => $collectionid, 'slug' => $newslug])) {
                    $problems[] = 'Conflit de slug histoire : ' . $newslug;
                }
            }
        } else {
            if ($DB->record_exists('local_cr_collection', ['slug' => $slug])) {
                $problems[] = 'Une collection portant ce slug existe déjà';
            }
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course || $courseid < 2 ||
                !preg_match('/qa|test/i', (string)($course->shortname ?? '') . ' ' . (string)($course->fullname ?? '')) ||
                $DB->record_exists('local_cr_collection', ['courseid' => $courseid])) {
                $problems[] = 'Sélectionne un cours QA sans collection';
            } elseif ($DB->record_exists_sql(
                'SELECT 1 FROM {user_enrolments} ue JOIN {enrol} e ON e.id=ue.enrolid WHERE e.courseid=:cid',
                ['cid' => $courseid])) {
                $problems[] = 'Ce cours QA contient déjà des inscriptions';
            }
        }
        return ['ready' => !$problems, 'problems' => $problems, 'stories' => count($manifest['stories']),
            'revisions' => count($manifest['revisions'] ?? []), 'slots' => count($manifest['slots'] ?? [])];
    }

    /** Import only to fresh QA destinations. No import of any publication, product or grant. */
    public static function import_qa(string $archive, string $sha256, int $actor, string $newslug,
        int $qaCollectionId = 0, int $qaCourseId = 0): array {
        global $DB;
        if (!self::sha($sha256) || !hash_equals($sha256, hash_file('sha256', $archive))) {
            self::error('Le ZIP a été modifié depuis son analyse');
        }
        $result = self::inspect_zip($archive); $m = $result['manifest'];
        if (!self::slug($newslug) || !preg_match('/(?:^|-)(?:qa|test)(?:-|$)/', $newslug)) {
            self::error('Nouveau slug QA requis : minuscules, chiffres et tirets');
        }
        if ($m['kind'] === 'anthology') {
            ReaderStoryTransferService::assert_qa_destination($qaCollectionId, self::new_story_slug($m['stories'][0]['slug'], $newslug, 1));
            if ($DB->record_exists('local_cr_anthology', ['slug' => $newslug])) { self::error('Recueil déjà existant'); }
        } else {
            if ($DB->record_exists('local_cr_collection', ['slug' => $newslug])) { self::error('Collection déjà existante'); }
            $course = $DB->get_record('course', ['id' => $qaCourseId], '*', MUST_EXIST);
            if ($qaCourseId < 2 || !preg_match('/qa|test/i', (string)$course->shortname . ' ' . (string)$course->fullname) ||
                $DB->record_exists('local_cr_collection', ['courseid' => $qaCourseId])) {
                self::error('Cours QA DEV non disponible ou déjà associé');
            }
            if ($DB->record_exists_sql('SELECT 1 FROM {user_enrolments} ue JOIN {enrol} e ON e.id=ue.enrolid WHERE e.courseid=:cid',
                ['cid' => $qaCourseId])) { self::error('Cours QA possédant des inscriptions : import interdit'); }
        }
        $z = new \ZipArchive();
        if ($z->open($archive, \ZipArchive::RDONLY) !== true) { self::error('ZIP impossible à rouvrir'); }
        $createdFiles = []; $newIds = []; $createdCollection = 0; $createdAnth = 0; $createdRevs = [];
        $tx = $DB->start_delegated_transaction();
        try {
            if ($m['kind'] === 'calendar') {
                $createdCollection = ReaderEditorialService::save_collection(0, $newslug, $m['object']['title'], $qaCourseId);
                $qaCollectionId = $createdCollection;
                $DB->update_record('local_cr_collection', (object)['id' => $createdCollection,
                    'timezone' => 'Europe/Paris', 'presentationmode' => $m['object']['presentationmode'],
                    'libraryafter' => $m['object']['libraryafter'], 'published' => 0]);
            }
            foreach ($m['stories'] as $s) {
                $targetSlug = self::new_story_slug($s['slug'], $newslug, $s['ref']);
                ReaderStoryTransferService::assert_qa_destination($qaCollectionId, $targetSlug);
                $temp = self::extract_tmp($z, $s['path']);
                try {
                    $bookid = ReaderStoryTransferService::import_qa($temp, $qaCollectionId, $targetSlug,
                        $actor, $s['sha256']);
                    $newIds[$s['ref']] = $bookid;
                    $createdFiles[] = $bookid;
                } finally { @unlink($temp); }
            }
            if ($m['kind'] === 'anthology') {
                $createdAnth = ReaderEditorialAnthologyService::save(0, $newslug, $m['object']['title'],
                    $m['object']['subtitle']);
                foreach ($m['chapters'] as $i => $ref) {
                    $DB->insert_record('local_cr_anthology_story', (object)[
                        'anthologyid' => $createdAnth, 'bookid' => $newIds[$ref],
                        'position' => $i + 1, 'timecreated' => time(),
                    ]);
                }
                foreach ($m['revisions'] as $revnum => $r) {
                    $raw = $z->getFromName($r['snapshot']);
                    $snapshot = ReaderAnthologySnapshotContract::decode($raw, $r['sha256']);
                    foreach ($snapshot['chapters'] as &$chapter) { $chapter['bookid'] = $newIds[$chapter['bookid']]; }
                    unset($chapter);
                    $json = ReaderAnthologySnapshotContract::encode($snapshot);
                    $revid = (int)$DB->insert_record('local_cr_anthrev', (object)[
                        'anthologyid' => $createdAnth, 'revno' => $revnum + 1,
                        'digest' => hash('sha256', $json), 'snapshotjson' => $json,
                        'state' => 'review', 'createdby' => $actor, 'timecreated' => time(),
                        'approvedby' => 0, 'approvedat' => 0,
                    ]);
                    $createdRevs[] = $revid;
                    foreach ($r['media'] as $j => $meta) {
                        $temp = self::extract_tmp($z, $meta['path']);
                        try {
                            $media = $snapshot['media'][$j];
                            self::assert_frozen_bytes($temp, $media);
                            ReaderStoryTransferService::store_verified_media([
                                'contextid' => (int)\context_system::instance()->id,
                                'component' => 'local_campusreader', 'filearea' => 'anthrev', 'itemid' => $revid,
                                'filepath' => $media['filepath'], 'filename' => $media['filename'],
                                'mimetype' => $media['mimetype'], 'userid' => $actor,
                                'license' => 'allrightsreserved',
                            ], $temp, (string)$media['sha1'], (int)$media['size'],
                                (string)$media['mimetype']);
                        } finally { @unlink($temp); }
                    }
                }
            } else {
                foreach ($m['slots'] as $slot) {
                    $DB->insert_record('local_cr_slot', (object)[
                        'collectionid' => $createdCollection, 'position' => $slot['position'],
                        'unlockat' => $slot['unlockat'], 'bookid' => $slot['story'] === null ? null : $newIds[$slot['story']],
                        'teaser' => $slot['teaser'], 'layoutsize' => $slot['layoutsize'],
                        'timecreated' => time(), 'timemodified' => time(),
                    ]);
                }
            }
            if ($m['kind'] === 'calendar' &&
                !set_config('advent_slotcount_' . $createdCollection,
                    (string)$m['object']['slotcount'], 'local_campusreader')) {
                self::error('Impossible de conserver la configuration des cases actives');
            }
            $tx->allow_commit();
            error_log('[CampusFR D2.6 import] actor=' . $actor . ' kind=' . $m['kind'] .
                ' qa=' . $newslug . ' stories=' . count($newIds) . ' private_revisions=' . count($createdRevs));
            return ['kind' => $m['kind'], 'id' => $createdAnth ?: $createdCollection,
                'stories' => count($newIds), 'revisions' => count($createdRevs)];
        } catch (\Throwable $e) {
            $ctx = (int)\context_system::instance()->id;
            foreach ($createdRevs as $id) {
                try { get_file_storage()->delete_area_files($ctx, 'local_campusreader', self::AREA, $id); }
                catch (\Throwable $ignored) {}
            }
            foreach ($createdFiles as $bid) {
                foreach (['cover','pageimage','narration','video','pdf'] as $area) {
                    try { get_file_storage()->delete_area_files($ctx, 'local_campusreader', $area, $bid); }
                    catch (\Throwable $ignored) {}
                }
            }
            $tx->rollback($e);
            throw $e;
        } finally { $z->close(); }
    }
    private static function new_story_slug(string $source, string $dest, int $ref): string {
        // Stable, collision-resistant-within-a-bundle; <64 bytes including numeric prefix.
        $slug = 'qa-' . $ref . '-' . substr(hash('sha256', $dest), 0, 8) . '-' . substr($source, 0, 46);
        return rtrim(substr($slug, 0, 64), '-');
    }
}
