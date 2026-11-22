<?php
// CampusFR D2.4.3 — strict Native Commerce rights for immutable published editions.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class publication_access {
    public static function key(string $slug): string {
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug)) {
            throw new \invalid_parameter_exception('Invalid publication slug');
        }
        return 'reader:publication:' . $slug;
    }

    public static function publication(string $slug): ?\stdClass {
        global $DB;
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug)) { return null; }
        $pub = $DB->get_record('local_cr_publication', ['slug' => $slug], '*', IGNORE_MISSING);
        return $pub ?: null;
    }

    /** No grants inferred from an email address, course enrollment, purchase cart or planned ledger. */
    public static function can_view(\stdClass $pub, ?int $userid = null, ?int $now = null): bool {
        global $DB, $USER;
        $userid ??= (int)($USER->id ?? 0);
        $now ??= time();
        if ((string)$pub->status !== 'published' || $userid <= 1 ||
                !isloggedin() || isguestuser() || $userid !== (int)$USER->id ||
                !empty($USER->suspended) || !empty($USER->deleted) ||
                (int)$pub->revisionid <= 0 || trim((string)($pub->productsku ?? '')) === '') {
            return false;
        }
        $productsku = strtoupper((string)$pub->productsku);
        $key = self::key((string)$pub->slug);
        // Only fulfilled, currently active grants with a concrete Moodle beneficiary.
        return $DB->record_exists_select('local_subs_commerce_grant',
            'type = :type AND resourcekey = :resourcekey AND productsku = :sku ' .
            'AND beneficiaryuserid = :userid AND status = :status ' .
            'AND validfrom <= :nowfrom AND (validuntil IS NULL OR validuntil > :nowuntil)',
            ['type' => 'reader_access', 'resourcekey' => $key, 'sku' => $productsku,
                'userid' => $userid, 'status' => 'active', 'nowfrom' => $now, 'nowuntil' => $now]);
    }

    /** Return the frozen released chapter map. Calendar access never reads live slots. */
    public static function chapter_allowed(\stdClass $pub, int $chapter, ?int $now = null, ?array $snapshot = null): bool {
        $now ??= time();
        if ($chapter < 1 || $chapter > 100) { return false; }
        if ((string)$pub->kind === 'anthology') { return true; }
        if ((string)$pub->kind !== 'calendar') { return false; }
        $data = json_decode((string)$pub->calendarjson, true);
        if (!is_array($data) || (int)($data['schema'] ?? 0) !== 1 ||
                !is_array($data['slots'] ?? null) || !array_is_list($data['slots'])) {
            return false;
        }
        foreach ($data['slots'] as $slot) {
            if ((int)($slot['chapter'] ?? 0) === $chapter) {
                $unlockat = (int)($slot['unlockat'] ?? PHP_INT_MAX);
                if ($unlockat > $now || $unlockat <= 0) { return false; }
                // Existing historical publications without a policy retain their original semantics.
                if (!array_key_exists('releasepolicy', $data)) { return true; }
                if ($data['releasepolicy'] !== 'chapter-ready-v1') { return false; }
                if ($snapshot === null) {
                    try { $snapshot = self::frozen($pub); }
                    catch (\Throwable $e) { return false; }
                }
                return launch_release_policy::chapter_ready($snapshot['chapters'][$chapter - 1] ?? null);
            }
        }
        return false;
    }

    public static function require_customer(string $slug, ?int $chapter = null): \stdClass {
        $pub = self::publication($slug);
        if (!$pub || !self::can_view($pub) ||
                ($chapter !== null && !self::chapter_allowed($pub, $chapter))) {
            // Deny resource enumeration without revealing whether it exists or is locked.
            throw new \moodle_exception('invalidaccess', 'error');
        }
        return $pub;
    }

    /** Strictly return the version bound to this edition. */
    public static function frozen(\stdClass $pub): array {
        global $DB;
        $rev = $DB->get_record('local_cr_anthrev',
            ['id' => (int)$pub->revisionid, 'anthologyid' => (int)$pub->anthologyid], '*', IGNORE_MISSING);
        if (!$rev || (string)$rev->state !== 'approved') {
            throw new \moodle_exception('invalidaccess', 'error');
        }
        $snapshot = \local_subscriptions\crm\library\ReaderAnthologySnapshotContract::decode(
            (string)$rev->snapshotjson, (string)$rev->digest);
        return $snapshot;
    }

    /** Customer library listing, only purchased publications with current grants. */
    public static function owned(): array {
        global $DB, $USER;
        if (!isloggedin() || isguestuser() || !empty($USER->suspended) ||
                !$DB->get_manager()->table_exists(new \xmldb_table('local_cr_publication'))) { return []; }
        $pubs = $DB->get_records('local_cr_publication', ['status' => 'published'], 'id ASC', '*', 0, 200);
        $result = [];
        foreach ($pubs as $pub) {
            if (!self::can_view($pub)) { continue; }
            try {
                $snapshot = self::frozen($pub);
                $title = (string)$snapshot['anthology']['title'];
                $result[] = ['slug' => (string)$pub->slug, 'kind' => (string)$pub->kind, 'title' => $title,
                    'url' => (new \moodle_url('/local/campusreader/publication.php',
                        ['slug' => (string)$pub->slug]))->out(false)];
            } catch (\Throwable $e) { continue; }
        }
        return $result;
    }
}
