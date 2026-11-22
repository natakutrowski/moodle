<?php
// CampusFR D2.9a: append-only private editorial narration alignments (NO Poodll dependency).
declare(strict_types=1);
namespace local_subscriptions\crm\library;

defined('MOODLE_INTERNAL') || die();

final class ReaderNarrationAlignmentService {
    private const AREA = 'audioalignment';
    private const MAX_CUES = 1200;
    private const MAX_MS = 7200000; // Two hours; exact audio length is additionally checked in the editor.
    private const MAX_BYTES = 900000;

    public static function segments(int $bookid): array {
        $rows = [];
        foreach (ReaderEditorialPageService::pages($bookid) as $page) {
            $paragraphs = json_decode((string)$page->paragraphsjson, true);
            if (!is_array($paragraphs)) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Paragraphes corrompus');
            }
            $n = 0;
            foreach ($paragraphs as $paragraph) {
                if (!is_string($paragraph) || trim($paragraph) === '') {
                    continue;
                }
                // Non-destructive segmentation: each original phrase is embedded in the private snapshot.
                // Punctuation/abbreviations may need editorial review; no automatic approval.
                $parts = preg_split('/(?<=[.!?…])\s+(?=[«“"\p{Lu}\p{N}])/u', trim($paragraph));
                if (!is_array($parts)) {
                    throw new \moodle_exception('invalidparameter');
                }
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $rows[] = [
                            'id' => 'p' . (int)$page->pageindex . 's' . $n++,
                            'page' => (int)$page->pageindex,
                            'title' => (string)$page->title,
                            'text' => $part,
                        ];
                    }
                    if (count($rows) > self::MAX_CUES) {
                        throw new \moodle_exception('invalidparameter', 'error', '', 'Trop de phrases pour une seule narration');
                    }
                }
            }
        }
        return $rows;
    }

    /** Return one private book-level narration, fail closed if ambiguous. */
    public static function audio(int $bookid): ?\stored_file {
        $files = get_file_storage()->get_area_files((int)\context_system::instance()->id,
            'local_campusreader', 'narration', $bookid, 'id ASC', false);
        $found = [];
        foreach ($files as $file) {
            if (!$file->is_directory() && $file->get_filepath() === '/' && $file->get_filesize() > 0) {
                $found[] = $file;
            }
        }
        return count($found) === 1 ? $found[0] : null;
    }

    public static function source(int $bookid): array {
        $segments = self::segments($bookid);
        $audio = self::audio($bookid);
        $digest = hash('sha256', json_encode(array_map(static fn($x) => [$x['id'], $x['text']], $segments),
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return ['segments' => $segments, 'textdigest' => $digest,
            'audiohash' => $audio ? $audio->get_contenthash() : '', 'audio' => $audio];
    }

    private static function private_files(int $bookid): array {
        return get_file_storage()->get_area_files((int)\context_system::instance()->id,
            'local_campusreader', self::AREA, $bookid, 'filename ASC', false);
    }

    public static function revisions(int $bookid): array {
        $revisions = [];
        $approvals = [];
        foreach (self::private_files($bookid) as $file) {
            if ($file->is_directory() || $file->get_filepath() !== '/' || $file->get_filesize() > self::MAX_BYTES) {
                continue;
            }
            $filename = $file->get_filename();
            if (preg_match('/\Arev-([0-9]{6})-([0-9a-f]{16})\.json\z/', $filename, $m)) {
                $number = (int)$m[1];
                // Original file is immutable. Reject mismatched/corrupt files rather than silently approving.
                $json = $file->get_content();
                if (substr(hash('sha256', $json), 0, 16) !== $m[2]) {
                    continue;
                }
                $payload = json_decode($json, true);
                if (is_array($payload) && ($payload['schema'] ?? '') === 'campusfr.narration-alignment.v1'
                        && (int)($payload['bookid'] ?? 0) === $bookid
                        && (int)($payload['revision'] ?? 0) === $number) {
                    $revisions[$number] = ['file' => $file, 'payload' => $payload,
                        'digest' => hash('sha256', $json), 'approved' => false];
                }
            } else if (preg_match('/\Aapproved-([0-9]{6})-([0-9a-f]{16})\.json\z/', $filename, $m)) {
                $body = $file->get_content();
                if (substr(hash('sha256', $body), 0, 16) !== $m[2]) {
                    continue;
                }
                $approval = json_decode($body, true);
                if (is_array($approval) && (int)($approval['bookid'] ?? 0) === $bookid &&
                        (int)($approval['revision'] ?? 0) === (int)$m[1]) {
                    $approvals[(int)$m[1]][] = $approval;
                }
            }
        }
        ksort($revisions);
        foreach ($revisions as $num => &$revision) {
            foreach ($approvals[$num] ?? [] as $approval) {
                if (hash_equals($revision['digest'], (string)($approval['digest'] ?? ''))) {
                    $revision['approved'] = true;
                    break;
                }
            }
        }
        unset($revision);
        return $revisions;
    }

    public static function validate_cues(array $submitted, array $segments, bool $requirecomplete): array {
        if (count($submitted) !== count($segments) || count($segments) > self::MAX_CUES) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Nombre de phrases incohérent');
        }
        $result = [];
        $previousend = 0;
        $timed = 0;
        foreach ($segments as $i => $segment) {
            if (!isset($submitted[$i]) || !is_array($submitted[$i]) ||
                    ($submitted[$i]['id'] ?? null) !== $segment['id']) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Séquence de phrases modifiée');
            }
            $skip = ($submitted[$i]['skip'] ?? false) === true;
            $start = $submitted[$i]['startms'] ?? null;
            $end = $submitted[$i]['endms'] ?? null;
            if ($skip) {
                if ($start !== null || $end !== null) {
                    throw new \moodle_exception('invalidparameter');
                }
            } else if ($start === null && $end === null && !$requirecomplete) {
                // Partial editorial review is allowed, but cannot be approved.
            } else {
                if (!is_int($start) || !is_int($end) || $start < 0 || $end <= $start ||
                        $end > self::MAX_MS || $start < $previousend) {
                    throw new \moodle_exception('invalidparameter', 'error', '',
                        'Les repères doivent être positifs, ordonnés et sans chevauchement');
                }
                $previousend = $end;
                $timed++;
            }
            $result[] = ['id' => $segment['id'], 'text' => $segment['text'],
                'page' => $segment['page'], 'skip' => $skip,
                'startms' => $skip ? null : $start, 'endms' => $skip ? null : $end];
        }
        if ($requirecomplete && $timed === 0) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Aucune phrase alignée');
        }
        return $result;
    }

    private static function assert_editable(int $bookid): array {
        ReaderEditorialPageService::assert_draft($bookid);
        $source = self::source($bookid);
        if (!$source['audio'] || !$source['segments']) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Importe une narration et des pages avant de synchroniser');
        }
        return $source;
    }

    public static function save(int $bookid, int $base, string $textdigest, string $audiohash,
        string $json, int $actor): int {
        if (strlen($json) > self::MAX_BYTES || strlen($json) < 2) {
            throw new \moodle_exception('invalidparameter');
        }
        $source = self::assert_editable($bookid);
        if (!hash_equals($source['textdigest'], $textdigest) ||
                !hash_equals($source['audiohash'], $audiohash)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Le texte ou la narration a changé : actualise la page');
        }
        $revs = self::revisions($bookid);
        $latest = $revs ? (int)array_key_last($revs) : 0;
        if ($base !== $latest || $latest >= 999999) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Une nouvelle version a été enregistrée. Recharge la page');
        }
        $cues = json_decode($json, true);
        if (!is_array($cues) || !array_is_list($cues)) {
            throw new \moodle_exception('invalidparameter');
        }
        $valid = self::validate_cues($cues, $source['segments'], false);
        $revision = $latest + 1;
        $body = json_encode(['schema' => 'campusfr.narration-alignment.v1',
            'bookid' => $bookid, 'revision' => $revision, 'authorid' => $actor,
            'createdat' => time(), 'textdigest' => $source['textdigest'],
            'audiohash' => $source['audiohash'], 'cues' => $valid],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($body) > self::MAX_BYTES) {
            throw new \moodle_exception('invalidparameter');
        }
        self::store($bookid, 'rev-' . sprintf('%06d', $revision) . '-' .
            substr(hash('sha256', $body), 0, 16) . '.json', $body, $actor);
        return $revision;
    }

    public static function approve(int $bookid, int $revision, string $digest, int $actor): void {
        $source = self::assert_editable($bookid);
        $revs = self::revisions($bookid);
        $latest = $revs ? (int)array_key_last($revs) : 0;
        if ($revision < 1 || $revision !== $latest || !isset($revs[$revision]) ||
                !hash_equals($revs[$revision]['digest'], $digest)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Révision expirée : actualise la page');
        }
        $payload = $revs[$revision]['payload'];
        if (!hash_equals($source['textdigest'], (string)$payload['textdigest']) ||
                !hash_equals($source['audiohash'], (string)$payload['audiohash'])) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Source modifiée depuis cette version');
        }
        self::validate_cues($payload['cues'], $source['segments'], true);
        if ($revs[$revision]['approved']) {
            return;
        }
        $body = json_encode(['bookid' => $bookid, 'revision' => $revision,
            'digest' => $digest, 'approvedby' => $actor, 'approvedat' => time()], JSON_THROW_ON_ERROR);
        self::store($bookid, 'approved-' . sprintf('%06d', $revision) . '-' .
            substr(hash('sha256', $body), 0, 16) . '.json', $body, $actor);
    }

    /**
     * Export an immutable and approved phrase map for a NEW editorial snapshot.
     * Never fall back to a previous story version after text/audio edits.
     * No public endpoint uses this service; released editions read only frozen JSON.
     */
    public static function approved_snapshot(int $bookid): ?array {
        $source = self::source($bookid);
        if (!$source['audio'] || !$source['segments']) { return null; }
        $latest = null;
        foreach (self::revisions($bookid) as $revision) {
            if (!$revision['approved']) { continue; }
            $body = $revision['payload'];
            if (!hash_equals($source['textdigest'], (string)($body['textdigest'] ?? '')) ||
                    !hash_equals($source['audiohash'], (string)($body['audiohash'] ?? ''))) {
                continue;
            }
            try {
                $valid = self::validate_cues($body['cues'], $source['segments'], true);
            } catch (\Throwable $ignored) {
                continue;
            }
            $latest = ['schema' => 1, 'textdigest' => $source['textdigest'],
                'audiohash' => $source['audiohash'], 'cues' => $valid];
        }
        return $latest;
    }

    private static function store(int $bookid, string $name, string $body, int $actor): void {
        $fs = get_file_storage();
        $record = ['contextid' => (int)\context_system::instance()->id,
            'component' => 'local_campusreader', 'filearea' => self::AREA,
            'itemid' => $bookid, 'filepath' => '/', 'filename' => $name,
            'mimetype' => 'application/json', 'userid' => $actor,
            'license' => 'allrightsreserved', 'author' => 'CampusFR'];
        if ($fs->file_exists($record['contextid'], $record['component'], $record['filearea'],
                $record['itemid'], $record['filepath'], $record['filename'])) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Une version identique existe déjà');
        }
        $fs->create_file_from_string($record, $body);
    }
}
