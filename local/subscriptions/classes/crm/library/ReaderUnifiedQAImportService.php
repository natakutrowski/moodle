<?php
/** CampusFR D2.17.3.2 - server-side unified QA import, DEV only. */
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderUnifiedQAImportService {
    private const MEDIA_AREAS = ['cover','pageimage','narration','video','pdf'];
    private static function must(bool $condition, string $message): void {
        if (!$condition) { throw new \DomainException($message); }
    }
    public static function book_slug(string $source, string $destination, int $ref): string {
        $slug = 'qa-' . $ref . '-' . substr(hash('sha256', $destination), 0, 8) . '-' .
            substr($source, 0, 46);
        return rtrim(substr($slug, 0, 64), '-');
    }
    public static function story_refs(array $calStories, array $anthStories,
        array $chapters, array $slugToBook): array {
        $n = count($calStories);
        self::must($n > 0 && $n <= 31 && count($anthStories) === $n &&
            count($slugToBook) === $n && count($chapters) === $n, 'Cardinalité des histoires incohérente');
        $bySlug = []; $byAnthRef = [];
        foreach ($calStories as $s) {
            self::must(is_int($s['ref'] ?? null) && is_string($s['slug'] ?? null) &&
                !isset($bySlug[$s['slug']]) && $s['slug'] !== '', 'Histoires du calendrier invalides');
            $bySlug[$s['slug']] = (int)($slugToBook[$s['slug']] ?? 0);
            self::must($bySlug[$s['slug']] > 0, 'Livre importé manquant');
        }
        self::must(count(array_unique(array_values($bySlug))) === $n, 'Réutilisation non autorisée d’un livre');
        foreach ($anthStories as $s) {
            self::must(is_int($s['ref'] ?? null) && $s['ref'] > 0 &&
                $s['ref'] <= $n && !isset($byAnthRef[$s['ref']]) &&
                isset($bySlug[$s['slug'] ?? '']), 'Référence recueil non retrouvée');
            $byAnthRef[$s['ref']] = $bySlug[$s['slug']];
        }
        self::must(count($byAnthRef) === $n, 'Références recueil incomplètes');
        $ordered = []; $seen = [];
        foreach ($chapters as $ref) {
            self::must(is_int($ref) && isset($byAnthRef[$ref]) && !isset($seen[$ref]),
                'Chapitre absent ou dupliqué');
            $seen[$ref] = true; $ordered[] = $byAnthRef[$ref];
        }
        return ['refs' => $byAnthRef, 'ordered' => $ordered];
    }
    private static function story_map(array $calmanifest, int $collid, string $calSlug): array {
        global $DB;
        $map = [];
        foreach ($calmanifest['stories'] as $s) {
            $book = $DB->get_record('local_cr_book', ['collectionid' => $collid,
                'slug' => self::book_slug($s['slug'], $calSlug, $s['ref'])],
                'id,title,subtitle,published');
            self::must($book !== false && (int)$book->published === 0,
                'Livre absent de la copie : ' . $s['slug']);
            $map[$s['slug']] = (int)$book->id;
        }
        self::must(count($map) === count($calmanifest['stories']) &&
            count(array_unique(array_values($map))) === count($map),
            'Correspondance canonique des livres incohérente');
        return $map;
    }
    private static function extract(\ZipArchive $zip, string $entry, int $bytes, int $max): string {
        self::must($bytes > 0 && $bytes <= $max && $zip->statName($entry) !== false,
            'Entrée ZIP refusée ou trop volumineuse');
        $tmp = tempnam(make_request_directory(), 'cfr-uni-');
        self::must(is_string($tmp), 'Temporaire de média indisponible');
        $source = $zip->getStream($entry);
        if (!is_resource($source)) { @unlink($tmp); throw new \RuntimeException('Flux ZIP inaccessible'); }
        $out = @fopen($tmp, 'wb');
        if (!is_resource($out)) { fclose($source); @unlink($tmp); throw new \RuntimeException('Temporaire inaccessible'); }
        try {
            $copied = stream_copy_to_stream($source, $out, $bytes + 1);
            self::must($copied === $bytes, 'Entrée ZIP de taille divergente');
        } finally { fclose($source); fclose($out); }
        return $tmp;
    }
    private static function verify_media_temp(string $temp, array $meta, array $frozen): void {
        self::must(filesize($temp) === (int)$meta['bytes'] &&
            (int)$meta['bytes'] === (int)$frozen['size'] &&
            hash_equals((string)$meta['sha256'], (string)hash_file('sha256', $temp)) &&
            hash_equals((string)$frozen['sha1'], (string)hash_file('sha1', $temp)),
            'Média figé différent du manifeste ou du snapshot');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temp);
        $allowed = match ((string)$frozen['mimetype']) {
            'audio/mpeg','audio/mp3' => ['audio/mpeg','audio/mp3'],
            'audio/mp4','audio/x-m4a' => ['audio/mp4','audio/x-m4a','video/mp4'],
            'audio/ogg','application/ogg' => ['audio/ogg','application/ogg'],
            default => [$frozen['mimetype']],
        };
        self::must(in_array($mime, $allowed, true), 'MIME réel du média figé différent');
        if (in_array($frozen['area'], ['cover','pageimage'], true)) {
            $shape = @getimagesize($temp);
            self::must((bool)$shape && $shape[0] > 0 && $shape[1] > 0 &&
                $shape[0] * $shape[1] <= 35000000, 'Image figée invalide');
        }
    }
    private static function import_shared_anthology(string $path, array $manifest,
        array $calmanifest, array $books, string $newslug, int $actor): array {
        global $DB;
        $refs = self::story_refs($calmanifest['stories'], $manifest['stories'],
            $manifest['chapters'], $books);
        self::must(!$DB->record_exists('local_cr_anthology', ['slug' => $newslug]) &&
            count($manifest['revisions']) === 1, 'Recueil destination déjà existant ou révisions multiples');
        $zip = new \ZipArchive();
        self::must($zip->open($path, \ZipArchive::RDONLY) === true, 'ZIP recueil inaccessible');
        $created = [];
        $tx = $DB->start_delegated_transaction();
        try {
            $anthid = ReaderEditorialAnthologyService::save(0, $newslug,
                (string)$manifest['object']['title'], (string)$manifest['object']['subtitle']);
            $now = time();
            foreach ($refs['ordered'] as $i => $bookid) {
                $DB->insert_record('local_cr_anthology_story', (object)[
                    'anthologyid' => $anthid, 'bookid' => $bookid,
                    'position' => $i + 1, 'timecreated' => $now,
                ]);
            }
            $rev = $manifest['revisions'][0];
            $raw = $zip->getFromName($rev['snapshot']);
            self::must(is_string($raw) && hash_equals((string)$rev['sha256'], hash('sha256', $raw)),
                'Snapshot portable divergent');
            $snapshot = ReaderAnthologySnapshotContract::decode($raw, (string)$rev['sha256']);
            self::must(count($snapshot['chapters']) === count($refs['ordered']) &&
                count($snapshot['media']) === count($rev['media']), 'Snapshot incompatible');
            foreach ($snapshot['chapters'] as $i => &$chapter) {
                $ref = $chapter['bookid'];
                self::must(is_int($ref) && $ref === $manifest['chapters'][$i] &&
                    isset($refs['refs'][$ref]), 'Chapitre figé non rattachable');
                $chapter['bookid'] = $refs['refs'][$ref];
            }
            unset($chapter);
            $json = ReaderAnthologySnapshotContract::encode($snapshot);
            $revisionid = (int)$DB->insert_record('local_cr_anthrev', (object)[
                'anthologyid' => $anthid, 'revno' => 1, 'digest' => hash('sha256', $json),
                'snapshotjson' => $json, 'state' => 'review', 'createdby' => $actor,
                'timecreated' => $now, 'approvedby' => 0, 'approvedat' => 0,
            ]);
            $created[] = $revisionid;
            foreach ($rev['media'] as $i => $media) {
                $descriptor = $snapshot['media'][$i];
                $temp = self::extract($zip, (string)$media['path'], (int)$media['bytes'], 209715200);
                try {
                    self::verify_media_temp($temp, $media, $descriptor);
                    ReaderStoryTransferService::store_verified_media([
                        'contextid' => (int)\context_system::instance()->id,
                        'component' => 'local_campusreader', 'filearea' => 'anthrev',
                        'itemid' => $revisionid, 'filepath' => $descriptor['filepath'],
                        'filename' => $descriptor['filename'], 'mimetype' => $descriptor['mimetype'],
                        'userid' => $actor, 'license' => 'allrightsreserved',
                    ], $temp, (string)$descriptor['sha1'], (int)$descriptor['size'],
                        (string)$descriptor['mimetype']);
                } finally { @unlink($temp); }
            }
            ReaderAnthologyRevisionService::assert_media($revisionid, $snapshot);
            $tx->allow_commit();
            return ['anthology' => $anthid, 'revision' => $revisionid];
        } catch (\Throwable $e) {
            foreach ($created as $revisionid) {
                try { get_file_storage()->delete_area_files((int)\context_system::instance()->id,
                    'local_campusreader', 'anthrev', $revisionid); }
                catch (\Throwable $ignored) {}
            }
            $tx->rollback($e);
            throw $e;
        } finally { $zip->close(); }
    }
    /** Check story pages and all file bytes, not merely imported identifiers. */
    private static function verify_stories(string $archive, array $manifest, array $books): array {
        global $DB;
        $zip = new \ZipArchive();
        self::must($zip->open($archive, \ZipArchive::RDONLY) === true, 'ZIP calendrier inaccessible');
        $storage = get_file_storage(); $ctx = (int)\context_system::instance()->id;
        $pagecount = 0; $mediacount = 0;
        try {
            foreach ($manifest['stories'] as $item) {
                $slug = $item['slug'];
                self::must(isset($books[$slug]), 'Livre attendu absent');
                $tmp = self::extract($zip, $item['path'], (int)$item['bytes'], 268435456);
                try {
                    self::must(hash_equals((string)$item['sha256'], (string)hash_file('sha256', $tmp)),
                        'ZIP d’histoire altéré');
                    $inspected = ReaderStoryTransferService::inspect_zip($tmp);
                    $source = $inspected['manifest'];
                    $bookid = (int)$books[$slug];
                    $book = $DB->get_record('local_cr_book', ['id' => $bookid], '*', MUST_EXIST);
                    self::must((string)$book->title === (string)$source['story']['title'] &&
                        (string)$book->subtitle === (string)$source['story']['subtitle'] &&
                        (int)$book->published === 0, 'Livre modifié ou publié');
                    $pages = array_values($DB->get_records('local_cr_page',
                        ['bookid' => $bookid], 'pageindex ASC'));
                    self::must(count($pages) === count($source['pages']), 'Pages manquantes');
                    $pagecount += count($pages);
                    foreach ($source['pages'] as $i => $expected) {
                        $page = $pages[$i];
                        $paras = json_decode((string)$page->paragraphsjson, true, 32, JSON_THROW_ON_ERROR);
                        self::must((int)$page->pageindex === $expected['pageindex'] &&
                            (string)$page->kind === $expected['kind'] &&
                            (string)$page->title === $expected['title'] &&
                            (string)($page->imagefile ?? '') === (string)($expected['imagefile'] ?? '') &&
                            (string)($page->imagealt ?? '') === (string)$expected['imagealt'] &&
                            $paras === $expected['paragraphs'], 'Texte ou page divergent');
                    }
                    $expectedMedia = [];
                    foreach ($source['media'] as $media) {
                        $filepath = $media['area'] === 'pageimage' ? '/p' . $media['pageindex'] . '/' : '/';
                        $key = $media['area'] . '|' . $filepath . '|' . $media['filename'];
                        self::must(!isset($expectedMedia[$key]), 'Média source dupliqué');
                        $expectedMedia[$key] = $media;
                    }
                    $seen = [];
                    foreach (self::MEDIA_AREAS as $area) {
                        foreach ($storage->get_area_files($ctx, 'local_campusreader', $area,
                            $bookid, 'id ASC', false) as $file) {
                            self::must(!$file->is_directory() && !$file->is_external_file() &&
                                (int)$file->get_status() === 0, 'Fichier non local');
                            $key = $area . '|' . $file->get_filepath() . '|' . $file->get_filename();
                            self::must(isset($expectedMedia[$key]) && !isset($seen[$key]),
                                'Fichier en trop, différent ou dupliqué');
                            $meta = $expectedMedia[$key];
                            self::must((int)$file->get_filesize() === $meta['bytes'] &&
                                hash_equals((string)$meta['sha1'], (string)$file->get_contenthash()),
                                'SHA1 ou taille divergent');
                            $h = $file->get_content_file_handle();
                            self::must(is_resource($h), 'Média illisible');
                            $hash1 = hash_init('sha1'); $hash256 = hash_init('sha256'); $n = 0;
                            try {
                                while (!feof($h)) {
                                    $b = fread($h, 1048576);
                                    self::must($b !== false && ($b !== '' || feof($h)),
                                        'Média tronqué');
                                    if ($b === '') { break; }
                                    $n += strlen($b);
                                    self::must($n <= $meta['bytes'], 'Média trop grand');
                                    hash_update($hash1, $b); hash_update($hash256, $b);
                                }
                            } finally { fclose($h); }
                            self::must($n === $meta['bytes'] &&
                                hash_equals((string)$meta['sha1'], hash_final($hash1)) &&
                                hash_equals((string)$meta['sha256'], hash_final($hash256)),
                                'Octets média du livre différents du ZIP');
                            $seen[$key] = true; $mediacount++;
                        }
                    }
                    self::must(count($seen) === count($expectedMedia), 'Média de livre manquant');
                } finally { @unlink($tmp); }
            }
        } finally { $zip->close(); }
        return ['pages' => $pagecount, 'storymedia' => $mediacount];
    }
    private static function verify_calendar(array $info, array $state, array $books): void {
        global $DB;
        $collid = (int)$state['collection'];
        $col = $DB->get_record('local_cr_collection', ['id' => $collid], '*', MUST_EXIST);
        $courseid = (int)$state['course'];
        self::must((string)$col->slug === (string)$state['calendar_slug'] &&
            (int)$col->courseid === $courseid && (int)$col->published === 0 &&
            (string)$col->timezone === 'Europe/Paris' &&
            !$DB->record_exists('local_cr_publication', ['collectionid' => $collid]) &&
            $DB->count_records('local_cr_collection', ['courseid' => $courseid]) === 1 &&
            $DB->count_records('local_cr_book', ['collectionid' => $collid]) === count($books),
            'Calendrier QA modifié, publié ou contenant des livres en trop');
        $manifest = $info['calendar_manifest'];
        self::must((int)$col->libraryafter === (int)$manifest['object']['libraryafter'] &&
            (string)$col->presentationmode === (string)$manifest['object']['presentationmode'],
            'Configuration calendrier divergente');
        $slots = ReaderEditorialCalendarService::slots($collid);
        self::must(count($slots) === count($manifest['slots']) &&
            ReaderEditorialCalendarService::count($col) === count($manifest['slots']),
            'Cases du calendrier manquantes');
        foreach ($manifest['slots'] as $s) {
            $slot = $slots[$s['position']] ?? null;
            $slug = $s['story'] === null ? null : $manifest['stories'][$s['story'] - 1]['slug'];
            self::must($slot && (int)$slot->position === (int)$s['position'] &&
                (int)$slot->unlockat === (int)$s['unlockat'] &&
                (string)$slot->teaser === (string)$s['teaser'] &&
                (string)$slot->layoutsize === (string)$s['layoutsize'] &&
                (int)$slot->bookid === (int)($slug === null ? 0 : $books[$slug]),
                'Position, date ou lien de case divergent');
        }
    }
    private static function verify_anthology(array $info, array $state, array $books): void {
        global $DB;
        $anthid = (int)$state['anthology']; $revid = (int)$state['revision'];
        $anth = $DB->get_record('local_cr_anthology', ['id' => $anthid], '*', MUST_EXIST);
        self::must((string)$anth->slug === (string)$state['anthology_slug'] &&
            (string)$anth->status === 'draft' &&
            !$DB->record_exists('local_cr_publication', ['anthologyid' => $anthid]),
            'Recueil QA modifié ou publié');
        $chapters = ReaderEditorialAnthologyService::chapters($anthid);
        $manifest = $info['anthology_manifest'];
        $expected = self::story_refs($info['calendar_manifest']['stories'],
            $manifest['stories'], $manifest['chapters'], $books);
        self::must(count($chapters) === count($expected['ordered']), 'Nombre de chapitres divergent');
        foreach ($expected['ordered'] as $i => $bookid) {
            self::must((int)$chapters[$i]->bookid === (int)$bookid &&
                (int)$chapters[$i]->collectionid === (int)$state['collection'],
                'Chapitre rattaché au mauvais livre');
        }
        $revs = $DB->get_records('local_cr_anthrev', ['anthologyid' => $anthid], 'id ASC');
        self::must(count($revs) === 1 && isset($revs[$revid]) &&
            (string)$revs[$revid]->state === 'review' &&
            (int)$revs[$revid]->approvedby === 0 && (int)$revs[$revid]->approvedat === 0,
            'La révision doit être en revue et non approuvée');
        $snap = ReaderAnthologyRevisionService::snapshot($anthid, $revid);
        self::must(count($snap['chapters']) === count($expected['ordered']) &&
            count($snap['media']) === count($manifest['revisions'][0]['media']),
            'Snapshot incomplet');
        foreach ($snap['chapters'] as $i => $chapter) {
            self::must((int)$chapter['bookid'] === (int)$expected['ordered'][$i],
                'Snapshot lié au mauvais livre');
        }
        ReaderAnthologyRevisionService::assert_media($revid, $snap);
    }
    private static function check_course(int $id): void {
        global $DB;
        $c = $DB->get_record('course', ['id' => $id], 'id,visible,shortname,fullname', MUST_EXIST);
        self::must($id > 1 && (int)$c->visible === 0 &&
            preg_match('/qa|test/i', (string)$c->shortname . ' ' . (string)$c->fullname) === 1 &&
            !$DB->record_exists_sql('SELECT 1 FROM {user_enrolments} ue JOIN {enrol} e '
                . 'ON e.id=ue.enrolid WHERE e.courseid=:courseid', ['courseid' => $id]),
            'Cours QA non masqué, non QA ou comportant des inscriptions');
    }
    public static function process(string $id): void {
        global $DB;
        ReaderUnifiedQAImportJob::ensure_dev();
        // Only one worker at a time, and no concurrent QA imports in this pipeline.
        $lock = ReaderUnifiedQAImportJob::acquire_lock($id);
        try {
            $state = ReaderUnifiedQAImportJob::load($id);
            $resuming = $state['status'] === 'resume_queued';
            if (!in_array($state['status'], ['queued','resume_queued'], true)) {
                // Ad hoc task replay after a completed or stopped job must have no side effects.
                return;
            }
            $actor = (int)$state['actor'];
            $user = $DB->get_record('user', ['id' => $actor], '*');
            self::must($user !== false && empty($user->deleted) && empty($user->suspended) &&
                is_siteadmin($actor), 'Administrateur QA invalide ou droits retirés');
            $paths = ReaderUnifiedQAImportJob::archives($id, $state);
            $pair = ReaderUnifiedBundlePlan::inspect_pair($paths['calendar'], $paths['anthology']);
            self::must((int)$pair['stories'] === (int)$state['count'] &&
                hash_equals((string)$state['sha']['calendar'], $pair['calendar_sha256']) &&
                hash_equals((string)$state['sha']['anthology'], $pair['anthology_sha256']),
                'ZIP ou nombre d’histoires divergent');
            self::check_course((int)$state['course']);
            if (!$resuming) {
                $plan = ReaderUnifiedBundlePlan::destination_plan($pair,
                    (string)$state['calendar_slug'], (string)$state['anthology_slug'], (int)$state['course']);
                self::must((bool)$plan['ready'], 'Destination QA occupée ou non conforme');
            } else {
                self::must((int)$state['collection'] > 0 && (int)$state['anthology'] === 0 &&
                    !$DB->record_exists('local_cr_anthology', ['slug' => $state['anthology_slug']]),
                    'Reprise refusée : recueil existant');
            }
            \core\session\manager::set_user($user);
            $state['status'] = 'running';
            $state['attempts'] = (int)$state['attempts'] + 1;
            $state['phase'] = $resuming ? 'validate_existing_calendar' : 'import_calendar';
            $state['message'] = 'Traitement en cours sur DEV.';
            ReaderUnifiedQAImportJob::save($id, $state);
            try {
                if (!$resuming) {
                    $result = ReaderLibraryBundleService::import_qa($paths['calendar'],
                        $pair['calendar_sha256'], $actor, $state['calendar_slug'], 0, (int)$state['course']);
                    self::must($result['kind'] === 'calendar' &&
                        (int)$result['stories'] === (int)$pair['stories'], 'Import du calendrier incomplet');
                    $state['collection'] = (int)$result['id'];
                    $state['phase'] = 'import_anthology';
                    ReaderUnifiedQAImportJob::save($id, $state);
                }
                $books = self::story_map($pair['calendar_manifest'], (int)$state['collection'],
                    (string)$state['calendar_slug']);
                self::verify_calendar($pair, $state, $books);
                self::verify_stories($paths['calendar'], $pair['calendar_manifest'], $books);
                $result = self::import_shared_anthology($paths['anthology'],
                    $pair['anthology_manifest'], $pair['calendar_manifest'], $books,
                    (string)$state['anthology_slug'], $actor);
                $state['anthology'] = $result['anthology'];
                $state['revision'] = $result['revision'];
                $state['phase'] = 'final_verification';
                ReaderUnifiedQAImportJob::save($id, $state);
                self::verify_anthology($pair, $state, $books);
                // On supported 24-story QA fixture, also run the independent CRM audit.
                if ((int)$pair['stories'] === 24) {
                    ReaderUnifiedQAReadiness::inspect((int)$state['collection'], (int)$state['anthology']);
                }
                $state['status'] = 'complete';
                $state['phase'] = 'editorial_review';
                $state['message'] = 'Import terminé, vérifié ; édition en revue et non publiée.';
                ReaderUnifiedQAImportJob::save($id, $state);
                error_log('[CampusFR D2.17.3.2] complete job=' . $id . ' actor=' . $actor .
                    ' calendar=' . (int)$state['collection'] . ' anthology=' . (int)$state['anthology']);
            } catch (\Throwable $e) {
                $partial = (int)$state['collection'] > 0 && (int)$state['anthology'] === 0;
                $state['status'] = $partial ? 'partial' : 'failed';
                $state['phase'] = 'stopped';
                $state['message'] = $partial
                    ? 'Calendrier QA conservé. Vérification et reprise contrôlée du recueil possibles.'
                    : 'Import arrêté. Vérifier le journal privé ; aucun nouvel essai automatique.';
                ReaderUnifiedQAImportJob::save($id, $state);
                error_log('[CampusFR D2.17.3.2] STOP job=' . $id . ' actor=' . $actor .
                    ' reason=' . get_class($e) . ' state=' . $state['status']);
            }
        } catch (\Throwable $e) {
            // A preflight failure must never be retried by cron indefinitely.
            $state = ReaderUnifiedQAImportJob::load($id);
            if (in_array($state['status'], ['queued','resume_queued'], true)) {
                $state['status'] = $state['status'] === 'resume_queued' ? 'partial' : 'failed';
                $state['phase'] = 'preflight_refused';
                $state['message'] = 'Préflight du job refusé. Corriger la destination avant un nouvel import.';
                ReaderUnifiedQAImportJob::save($id, $state);
            }
            error_log('[CampusFR D2.17.3.2] PREFLIGHT STOP job=' . $id .
                ' reason=' . get_class($e));
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}
