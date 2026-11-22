<?php
// CampusFR D2.4 — Anthologies: independent private editorial drafts, no grants or course dependence.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderEditorialAnthologyService {
    private const ANTH = 'local_cr_anthology';
    private const LINK = 'local_cr_anthology_story';
    private const LIMIT = 100;

    public static function ready(): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table(self::ANTH))
            && $DB->get_manager()->table_exists(new \xmldb_table(self::LINK));
    }
    public static function assert_ready(): void {
        if (!self::ready()) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Migration D2.4 DEV requise');
        }
    }
    public static function list_all(): array {
        global $DB;
        self::assert_ready();
        return array_values($DB->get_records(self::ANTH, [], 'id DESC', '*', 0, 500));
    }
    public static function anthology(int $id): \stdClass {
        global $DB;
        self::assert_ready();
        return $DB->get_record(self::ANTH, ['id' => $id], '*', MUST_EXIST);
    }
    public static function chapters(int $id): array {
        global $DB;
        self::anthology($id);
        return array_values($DB->get_records_sql(
            'SELECT l.id, l.anthologyid, l.bookid, l.position, l.timecreated, '
            . 'b.title, b.slug, b.collectionid, b.published, c.title AS collectiontitle '
            . 'FROM {local_cr_anthology_story} l '
            . 'JOIN {local_cr_book} b ON b.id = l.bookid '
            . 'JOIN {local_cr_collection} c ON c.id = b.collectionid '
            . 'WHERE l.anthologyid = :id ORDER BY l.position ASC', ['id' => $id]
        ));
    }
    public static function all_stories(): array {
        global $DB;
        return array_values($DB->get_records_sql(
            'SELECT b.id, b.title, b.slug, b.collectionid, c.title AS collectiontitle '
            . 'FROM {local_cr_book} b '
            . 'JOIN {local_cr_collection} c ON c.id = b.collectionid '
            . 'ORDER BY c.title ASC, b.title ASC', [], 0, 500));
    }
    public static function for_story(int $bookid): array {
        global $DB;
        if (!self::ready()) { return []; }
        return array_values($DB->get_records_sql(
            'SELECT a.id, a.title, a.slug, a.status '
            . 'FROM {local_cr_anthology} a '
            . 'JOIN {local_cr_anthology_story} l ON l.anthologyid = a.id '
            . 'WHERE l.bookid = :bid ORDER BY a.title ASC', ['bid' => $bookid]));
    }
    private static function clean(string $value, int $max, bool $required, string $errorid): string {
        $result = trim(strip_tags($value));
        if (($required && $result === '') || \core_text::strlen($result) > $max) {
            throw new \moodle_exception($errorid, 'local_subscriptions');
        }
        return $result;
    }
    /**
     * Friendly, stable URL identifier. A blank slug is generated from the title.
     * Same normalization convention as CommerceProductSlugService::clean().
     */
    private static function slug(string $value, string $title): string {
        $source = trim($value) !== '' ? $value : $title;
        $ascii = \core_text::strtolower(\core_text::specialtoascii(trim($source)));
        $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
        $slug = trim($ascii, '-');
        if ($slug === '' || strlen($slug) > 64) {
            throw new \moodle_exception('cfr_d241b_badslug', 'local_subscriptions');
        }
        return $slug;
    }
    private static function writable(\stdClass $anth, int $expected): void {
        if ($anth->status !== 'draft') {
            throw new \moodle_exception('invalidparameter', 'error', '',
                get_string('cfr_d24_locked', 'local_subscriptions'));
        }
        if ($expected !== (int)$anth->timemodified) {
            throw new \moodle_exception('invalidparameter', 'error', '',
                get_string('cfr_d24_concurrent', 'local_subscriptions'));
        }
    }
    private static function touch(int $id, int $previous): void {
        global $DB;
        $DB->update_record(self::ANTH, (object)[
            'id' => $id, 'timemodified' => max(time(), $previous + 1),
        ]);
    }
    private static function audit(string $event, int $id): void {
        global $USER;
        error_log('[CampusFR D2.4 anthology] event=' . $event . ' anthology=' . $id . ' actor=' . (int)$USER->id);
    }
    public static function save(int $id, string $slug, string $title, string $subtitle, int $expected = 0): int {
        global $DB;
        self::assert_ready();
        $title = self::clean($title, 191, true, 'cfr_d241b_badtitle');
        $subtitle = self::clean($subtitle, 255, false, 'cfr_d241b_badsubtitle');
        // Reject invalid input *before* opening a delegated transaction. The editor
        // deliberately catches validation errors in order to display them inline.
        if (!$id) {
            $slug = self::slug($slug, $title);
            if ($DB->record_exists(self::ANTH, ['slug' => $slug])) {
                throw new \moodle_exception('cfr_d241b_slugexists', 'local_subscriptions');
            }
        } else {
            $anth = self::anthology($id);
            self::writable($anth, $expected);
        }
        $tx = $DB->start_delegated_transaction();
        if (!$id) {
            $id = (int)$DB->insert_record(self::ANTH, (object)[
                'slug' => $slug, 'title' => $title, 'subtitle' => $subtitle,
                'status' => 'draft', 'timecreated' => time(), 'timemodified' => time(),
            ]);
        } else {
            $DB->update_record(self::ANTH, (object)[
                'id' => $id, 'title' => $title, 'subtitle' => $subtitle,
            ]);
            self::touch($id, (int)$anth->timemodified);
        }
        $tx->allow_commit();
        self::audit('save', $id);
        return $id;
    }
    public static function mutate(int $id, string $action, int $storyid, int $expected): void {
        global $DB;
        self::assert_ready();
        if (!in_array($action, ['add', 'up', 'down', 'remove'], true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $tx = $DB->start_delegated_transaction();
        $anth = self::anthology($id);
        self::writable($anth, $expected);
        $rows = array_values($DB->get_records(self::LINK, ['anthologyid' => $id], 'position ASC'));
        $count = count($rows);
        if ($action === 'add') {
            if ($count >= self::LIMIT || $storyid < 1
                || !$DB->record_exists('local_cr_book', ['id' => $storyid])
                || $DB->record_exists(self::LINK, ['anthologyid' => $id, 'bookid' => $storyid])) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    get_string('cfr_d24_badsource', 'local_subscriptions'));
            }
            $DB->insert_record(self::LINK, (object)[
                'anthologyid' => $id, 'bookid' => $storyid,
                'position' => $count + 1, 'timecreated' => time(),
            ]);
        } else {
            $found = null;
            foreach ($rows as $i => $row) {
                if ((int)$row->bookid === $storyid) { $found = $i; break; }
            }
            if ($found === null) { throw new \moodle_exception('invalidparameter'); }
            $item = $rows[$found];
            if ($action === 'remove') {
                $DB->delete_records(self::LINK, ['id' => $item->id, 'anthologyid' => $id]);
                for ($i = $found + 1; $i < $count; $i++) {
                    $DB->update_record(self::LINK, (object)[
                        'id' => $rows[$i]->id, 'position' => $i,
                    ]);
                }
            } else {
                $target = $action === 'up' ? $found - 1 : $found + 1;
                if ($target < 0 || $target >= $count) { throw new \moodle_exception('invalidparameter'); }
                $neighbor = $rows[$target];
                // 0 is a temporary gap: unique (anthologyid, position) remains respected.
                $DB->update_record(self::LINK, (object)['id' => $item->id, 'position' => 0]);
                $DB->update_record(self::LINK, (object)[
                    'id' => $neighbor->id, 'position' => (int)$item->position,
                ]);
                $DB->update_record(self::LINK, (object)[
                    'id' => $item->id, 'position' => (int)$neighbor->position,
                ]);
            }
        }
        self::touch($id, (int)$anth->timemodified);
        $tx->allow_commit();
        self::audit($action, $id);
    }
}
