<?php
// CampusFR D2.4.2a — private, append-only snapshots of anthology content + Moodle stored media.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderAnthologyRevisionService {
    private const TABLE = 'local_cr_anthrev';
    private const AREAS = ['cover', 'pageimage', 'narration', 'video', 'pdf'];

    public static function ready(): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table(self::TABLE));
    }
    public static function assert_ready(): void {
        if (!self::ready()) { throw new \moodle_exception('cfr_d242_migrate', 'local_subscriptions'); }
    }
    public static function list_for(int $anthologyid): array {
        global $DB;
        self::assert_ready();
        ReaderEditorialAnthologyService::anthology($anthologyid);
        return array_values($DB->get_records(self::TABLE, ['anthologyid' => $anthologyid], 'revno DESC',
            'id,anthologyid,revno,digest,state,createdby,timecreated,approvedby,approvedat'));
    }
    public static function get(int $anthologyid, int $revisionid): \stdClass {
        global $DB;
        self::assert_ready();
        ReaderEditorialAnthologyService::anthology($anthologyid);
        return $DB->get_record(self::TABLE, ['anthologyid' => $anthologyid, 'id' => $revisionid], '*', MUST_EXIST);
    }
    public static function snapshot(int $anthologyid, int $revisionid): array {
        $record = self::get($anthologyid, $revisionid);
        return ReaderAnthologySnapshotContract::decode((string)$record->snapshotjson, (string)$record->digest);
    }
    private static function source(int $id, array $readybooks = []): array {
        global $DB;
        $anth = ReaderEditorialAnthologyService::anthology($id);
        if ($anth->status !== 'draft') {
            throw new \moodle_exception('cfr_d242_draftonly', 'local_subscriptions');
        }
        $chapters = ReaderEditorialAnthologyService::chapters($id);
        if (!$chapters) { throw new \moodle_exception('cfr_d242_nochapters', 'local_subscriptions'); }
        $result = [
            'schema' => 1,
            'anthology' => ['slug' => (string)$anth->slug, 'title' => (string)$anth->title,
                'subtitle' => (string)$anth->subtitle],
            'chapters' => [], 'media' => [],
        ];
        $requested = [];
        foreach ($readybooks as $bookid) {
            if (!is_int($bookid) || $bookid < 1 || isset($requested[$bookid])) {
                throw new \invalid_parameter_exception('Invalid release-ready book list');
            }
            $requested[$bookid] = true;
        }
        $seenready = [];
        $sources = [];
        $fs = get_file_storage();
        $contextid = (int)\context_system::instance()->id;
        foreach ($chapters as $n => $chapter) {
            $ordinal = $n + 1;
            $bookid = (int)$chapter->bookid;
            $book = $DB->get_record('local_cr_book', ['id' => $bookid], '*', MUST_EXIST);
            $pages = array_values($DB->get_records('local_cr_page', ['bookid' => $bookid], 'pageindex ASC'));
            if (count($pages) > 100) {
                throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
            }
            $outpages = [];
            foreach ($pages as $idx => $p) {
                if ((int)$p->pageindex !== $idx) {
                    throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
                }
                $paragraphs = json_decode((string)$p->paragraphsjson, true);
                if (!is_array($paragraphs) || !array_is_list($paragraphs)) {
                    throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
                }
                $outpages[] = ['index' => $idx, 'kind' => (string)$p->kind,
                    'title' => (string)$p->title, 'paragraphs' => $paragraphs,
                    'imagefile' => (string)($p->imagefile ?? ''), 'imagealt' => (string)($p->imagealt ?? '')];
            }
            $frozenchapter = [
                'position' => $ordinal, 'bookid' => $bookid, 'slug' => (string)$book->slug,
                'title' => (string)$book->title, 'subtitle' => (string)$book->subtitle,
                'pages' => $outpages,
                'release_ready' => isset($requested[$bookid]),
            ];
            if (isset($requested[$bookid])) {
                // Explicit editorial decision + minimum genuine text. Titles-only stay closed.
                if (!\local_campusreader\local\launch_release_policy::has_content($outpages)) {
                    throw new \invalid_parameter_exception('Histoire non prête : compléter sa couverture et son texte avant de cocher la publication.');
                }
                $seenready[$bookid] = true;
            }
            // Copy approved alignments only into newly created immutable revisions.
            // Older published snapshots never gain or change phrase timing retroactively.
            $alignment = ReaderNarrationAlignmentService::approved_snapshot($bookid);
            if ($alignment !== null) {
                $frozenchapter['alignment'] = $alignment;
            }
            $result['chapters'][] = $frozenchapter;
            foreach (self::AREAS as $area) {
                $areaFiles = $fs->get_area_files($contextid, 'local_campusreader', $area, $bookid,
                    'id ASC', false);
                foreach ($areaFiles as $file) {
                    if ($file->is_directory()) { continue; }
                    $originalpath = (string)$file->get_filepath();
                    if ($area === 'pageimage') {
                        if (!preg_match('#\A/p(0|[1-9][0-9]{0,2})/\z#', $originalpath, $m)) {
                            throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
                        }
                        $pageidx = (int)$m[1];
                        if (!isset($outpages[$pageidx]) ||
                                $outpages[$pageidx]['imagefile'] !== $file->get_filename()) {
                            throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
                        }
                    } else if ($originalpath !== '/') {
                        throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
                    }
                    if ($file->get_filesize() < 1 || !in_array((string)$file->get_mimetype(),
                            [
                                'image/png', 'image/jpeg', 'image/webp', 'audio/mpeg', 'audio/mp3',
                                'audio/mp4', 'audio/x-m4a', 'audio/ogg', 'application/ogg',
                                'video/mp4', 'video/webm', 'application/pdf',
                            ], true) || !preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,119}\z/D',
                            $file->get_filename())) {
                        throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
                    }
                    $destpath = '/story' . $ordinal . '/' . $area . '/' .
                        ($area === 'pageimage' ? 'p' . $pageidx . '/' : '');
                    $result['media'][] = [
                        'chapter' => $ordinal, 'area' => $area, 'filename' => (string)$file->get_filename(),
                        'filepath' => $destpath, 'sha1' => (string)$file->get_contenthash(),
                        'mimetype' => (string)$file->get_mimetype(), 'size' => (int)$file->get_filesize(),
                    ];
                    $sources[] = $file;
                }
            }
            // Fail closed on a page pointing to an image no longer held by Moodle File API.
            foreach ($outpages as $pg) {
                if ($pg['imagefile'] === '') { continue; }
                $expectedpath = '/story' . $ordinal . '/pageimage/p' . $pg['index'] . '/';
                $found = false;
                foreach ($result['media'] as $mf) {
                    if ($mf['filepath'] === $expectedpath && $mf['filename'] === $pg['imagefile']) {
                        $found = true; break;
                    }
                }
                if (!$found) { throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions'); }
            }
        }
        if (count($seenready) !== count($requested)) {
            throw new \invalid_parameter_exception('Release-ready selection contains a book outside the anthology');
        }
        return [$anth, $result, $sources];
    }
    /** Save a private revision. Original stories and media are never modified. */
    public static function create(int $anthologyid, int $expectedmodified, array $readybooks = []): int {
        global $DB, $USER;
        self::assert_ready();
        try {
            [$anth, $snapshot, $sources] = self::source($anthologyid, $readybooks);
        } catch (\Throwable $e) {
            // A failing source validation is not the same problem as a failing private copy.
            error_log('[CampusFR D2.4.2a.4 revision] source-failed anthology=' . $anthologyid .
                ' exception=' . get_class($e) . ' message=' . $e->getMessage());
            throw $e;
        }
        if ((int)$anth->timemodified !== $expectedmodified) {
            throw new \moodle_exception('cfr_d242_concurrent', 'local_subscriptions');
        }
        $json = ReaderAnthologySnapshotContract::encode($snapshot);
        $sha = hash('sha256', $json);
        $latest = $DB->get_records(self::TABLE, ['anthologyid' => $anthologyid], 'revno DESC', '*', 0, 1);
        $last = $latest ? reset($latest) : false;
        $revno = $last ? (int)$last->revno + 1 : 1;
        if ($last && hash_equals((string)$last->digest, $sha)) {
            throw new \moodle_exception('cfr_d242_unchanged', 'local_subscriptions');
        }
        if (count($sources) !== count($snapshot['media'])) {
            error_log('[CampusFR D2.4.2a.4 revision] source-list mismatch anthology=' . $anthologyid);
            throw new \moodle_exception('cfr_d242_badsource', 'local_subscriptions');
        }
        $tx = $DB->start_delegated_transaction();
        $id = 0;
        $stage = 'insertion DB de la version';
        $invalidcopy = null;
        try {
            $id = (int)$DB->insert_record(self::TABLE, (object)[
                'anthologyid' => $anthologyid, 'revno' => $revno, 'digest' => $sha,
                'snapshotjson' => $json, 'state' => 'review', 'createdby' => (int)$USER->id,
                'timecreated' => time(), 'approvedby' => 0, 'approvedat' => 0,
            ]);
            $fs = get_file_storage();
            $ctx = (int)\context_system::instance()->id;
            foreach ($sources as $i => $original) {
                $media = $snapshot['media'][$i];
                $label = 'chapitre ' . (int)$media['chapter'] . ' · ' . $media['area'] .
                    ' · ' . $media['filepath'] . $media['filename'];
                $stage = 'copie privée ' . ($i + 1) . '/' . count($sources) . ': ' . $label;
                $target = [
                    'contextid' => $ctx, 'component' => 'local_campusreader',
                    'filearea' => 'anthrev', 'itemid' => $id,
                    'filepath' => $media['filepath'], 'filename' => $media['filename'],
                    'mimetype' => $media['mimetype'], 'userid' => (int)$USER->id,
                ];
                if (!($original instanceof \stored_file)) {
                    $invalidcopy = $label;
                    throw new \UnexpectedValueException('Source media object is not a stored_file');
                }
                // Create a new private File API record; source story remains unchanged.
                $copied = $fs->create_file_from_storedfile($target, $original);
                $stage = 'vérification après copie ' . ($i + 1) . '/' . count($sources) . ': ' . $label;
                // Independently fetch the newly stored file, not only the returned object.
                $stored = $fs->get_file($ctx, 'local_campusreader', 'anthrev', $id,
                    $media['filepath'], $media['filename']);
                // Moodle stored_file::get_filesize() returns the DB record's raw value.
                // Depending on the DB driver this may be "21252" rather than int(21252).
                // Compare canonical decimal representations, NOT strict PHP types.
                $badcopied = self::metadata_problems($copied, $media);
                $badstored = self::metadata_problems($stored, $media);
                if ($badcopied || $badstored) {
                    $invalidcopy = $label;
                    error_log('[CampusFR D2.4.2a.4 revision] COPY_MISMATCH anthology=' . $anthologyid .
                        ' revision=' . $id . ' index=' . ($i + 1) .
                        ' returned=' . implode(',', $badcopied ?: ['ok']) .
                        ' refetched=' . implode(',', $badstored ?: ['ok']));
                    throw new \UnexpectedValueException('Copied file metadata differs from source');
                }
            }
            $stage = 'validation transaction de la version';
            $tx->allow_commit();
        } catch (\Throwable $e) {
            // The log identifies the precise failing operation; do not expose SQL or paths publicly.
            error_log('[CampusFR D2.4.2a.4 revision] FAILED anthology=' . $anthologyid .
                ' revision=' . $id . ' stage=' . $stage .
                ' exception=' . get_class($e) . ' message=' . $e->getMessage());
            // Moodle File API writes can survive SQL rollback; remove only this revision's area.
            if ($id > 0) {
                try {
                    get_file_storage()->delete_area_files((int)\context_system::instance()->id,
                        'local_campusreader', 'anthrev', $id);
                } catch (\Throwable $cleanup) {
                    error_log('[CampusFR D2.4.2a.4 revision] cleanup-failed revision=' . $id .
                        ' exception=' . get_class($cleanup) . ' message=' . $cleanup->getMessage());
                }
            }
            try { $tx->rollback($e); } catch (\Throwable $rollback) { /* Keep the first cause. */ }
            // Only sanitized messages reach the CRM; technical details remain server-side.
            if ($invalidcopy !== null) {
                throw new \moodle_exception('cfr_d242_copyinvalid', 'local_subscriptions', '', $invalidcopy);
            }
            throw new \moodle_exception('cfr_d242_stageerror', 'local_subscriptions', '', $stage);
        }
        error_log('[CampusFR D2.4.2a.4 revision] CREATED anthology=' . $anthologyid .
            ' revision=' . $id . ' actor=' . (int)$USER->id . ' sha256=' . $sha);
        return $id;
    }
    /** Approval changes only workflow metadata. Snapshot bytes and JSON remain unchanged. */
    public static function approve(int $anthologyid, int $revisionid): void {
        global $DB, $USER;
        $rev = self::get($anthologyid, $revisionid);
        if ($rev->state !== 'review') { throw new \moodle_exception('cfr_d242_approved', 'local_subscriptions'); }
        $payload = self::snapshot($anthologyid, $revisionid);
        self::assert_media($revisionid, $payload);
        $DB->update_record(self::TABLE, (object)[
            'id' => $revisionid, 'state' => 'approved',
            'approvedby' => (int)$USER->id, 'approvedat' => time(),
        ]);
        error_log('[CampusFR D2.4.2a approval] anthology=' . $anthologyid .
            ' revision=' . $revisionid . ' actor=' . (int)$USER->id);
    }
    /**
     * Exact values, but independent of whether Moodle's DB driver returns an integer
     * filesize as int or decimal string. Never relax hashes or mimetypes.
     *
     * @param mixed $file The returned or re-fetched Moodle file.
     * @param array $media The validated snapshot media entry.
     * @return string[] Identifiers safe for server-side diagnostic logging.
     */
    private static function metadata_problems($file, array $media): array {
        if (!($file instanceof \stored_file)) {
            return ['missing'];
        }
        $errors = [];
        if ($file->is_directory()) { $errors[] = 'directory'; }
        if ($file->get_contenthash() !== $media['sha1']) { $errors[] = 'sha1'; }
        if ((string)$file->get_filesize() !== (string)$media['size']) { $errors[] = 'size'; }
        if ($file->get_mimetype() !== $media['mimetype']) { $errors[] = 'mimetype'; }
        return $errors;
    }

    public static function assert_media(int $revisionid, array $payload): void {
        $fs = get_file_storage();
        $ctx = (int)\context_system::instance()->id;
        foreach ($payload['media'] as $file) {
            $stored = $fs->get_file($ctx, 'local_campusreader', 'anthrev', $revisionid,
                $file['filepath'], $file['filename']);
            if (self::metadata_problems($stored, $file)) {
                throw new \moodle_exception('cfr_d242_mediaerror', 'local_subscriptions');
            }
        }
    }
    /** Only CRM guarded routes may invoke this; no guest or public pluginfile access. */
    public static function media(int $anthologyid, int $revisionid, int $mediaindex): \stored_file {
        $snapshot = self::snapshot($anthologyid, $revisionid);
        if ($mediaindex < 0 || !array_key_exists($mediaindex, $snapshot['media'])) {
            throw new \moodle_exception('invalidparameter');
        }
        $meta = $snapshot['media'][$mediaindex];
        $file = get_file_storage()->get_file((int)\context_system::instance()->id,
            'local_campusreader', 'anthrev', $revisionid, $meta['filepath'], $meta['filename']);
        if (self::metadata_problems($file, $meta)) {
            throw new \moodle_exception('cfr_d242_mediaerror', 'local_subscriptions');
        }
        return $file;
    }
}
