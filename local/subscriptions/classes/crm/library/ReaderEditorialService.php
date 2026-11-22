<?php
// CampusFR D2.1 — Source canonique: local_campusreader. Aucun droit commercial créé.
declare(strict_types=1);

namespace local_subscriptions\crm\library;

defined('MOODLE_INTERNAL') || die();

final class ReaderEditorialService {
    private const COLLECTION = 'local_cr_collection';
    private const BOOK = 'local_cr_book';

    public static function assert_ready(): void {
        global $DB;
        if ((int)get_config('local_campusreader', 'version') < 2026111302) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'CampusReader non installé');
        }
        foreach ([self::COLLECTION, self::BOOK, 'local_cr_page', 'local_cr_slot'] as $table) {
            if (!$DB->get_manager()->table_exists(new \xmldb_table($table))) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Schéma Reader incomplet');
            }
        }
    }

    public static function collections(): array {
        global $DB;
        return array_values($DB->get_records(self::COLLECTION, [], 'id ASC', '*', 0, 500));
    }

    public static function collection(int $id): \stdClass {
        global $DB;
        return $DB->get_record(self::COLLECTION, ['id' => $id], '*', MUST_EXIST);
    }

    public static function books(int $collectionid): array {
        global $DB;
        return array_values($DB->get_records(self::BOOK, ['collectionid' => $collectionid], 'id ASC', '*', 0, 250));
    }

    public static function book(int $id): \stdClass {
        global $DB;
        return $DB->get_record(self::BOOK, ['id' => $id], '*', MUST_EXIST);
    }

    private static function clean_title(string $title, int $limit = 191): string {
        $title = trim(strip_tags($title));
        if ($title === '' || \core_text::strlen($title) > $limit) {
            throw new \moodle_exception('invalidparameter');
        }
        return $title;
    }

    private static function clean_slug(string $slug): string {
        $slug = strtolower(trim($slug));
        if (strlen($slug) > 64 || !preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/D', $slug)) {
            throw new \moodle_exception('invalidparameter');
        }
        return $slug;
    }

    /** Crée une collection jamais publiée ; édite uniquement des brouillons. */
    public static function save_collection(int $id, string $slug, string $title, int $courseid): int {
        global $DB;
        $title = self::clean_title($title);
        $now = time();
        $tx = $DB->start_delegated_transaction();
        if ($id > 0) {
            $collection = self::collection($id);
            if ((int)$collection->published !== 0) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Collection publiée : lecture seule');
            }
            // Slug et cours support sont immuables après création : droits et liens existants.
            $DB->update_record(self::COLLECTION, (object)[
                'id' => $id, 'title' => $title, 'timemodified' => $now,
            ]);
        } else {
            $slug = self::clean_slug($slug);
            if ($courseid < 2 || !$DB->record_exists('course', ['id' => $courseid])
                || $DB->record_exists(self::COLLECTION, ['slug' => $slug])
                || $DB->record_exists(self::COLLECTION, ['courseid' => $courseid])) {
                // Un support unique par cours en D2.1 : évite les résolutions ambiguës.
                throw new \moodle_exception('invalidparameter', 'error', '', 'Cours support ou slug non disponible');
            }
            $id = (int)$DB->insert_record(self::COLLECTION, (object)[
                'slug' => $slug, 'title' => $title, 'courseid' => $courseid,
                'timezone' => 'Europe/Paris', 'presentationmode' => 'library',
                'libraryafter' => 0, 'published' => 0,
                'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        $tx->allow_commit();
        error_log('[CampusFR D2.1 editorial] collection=' . $id . ' actor=' . $GLOBALS['USER']->id . ' event=save');
        return $id;
    }

    /** Les fiches sont des brouillons. La création de pages/media/publication vient plus tard. */
    public static function save_book(int $id, int $collectionid, string $slug, string $title, string $subtitle): int {
        global $DB;
        $title = self::clean_title($title);
        $subtitle = trim(strip_tags($subtitle));
        if (\core_text::strlen($subtitle) > 255) {
            throw new \moodle_exception('invalidparameter');
        }
        $now = time();
        $tx = $DB->start_delegated_transaction();
        if ($id > 0) {
            $book = self::book($id);
            if ((int)$book->published !== 0 || $collectionid !== (int)$book->collectionid) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Livre publié ou collection modifiée');
            }
            $DB->update_record(self::BOOK, (object)[
                'id' => $id, 'title' => $title, 'subtitle' => $subtitle,
                'timemodified' => $now,
            ]);
        } else {
            $targetcollection = self::collection($collectionid);
            if ((int)$targetcollection->published !== 0) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Création dans collection publiée interdite');
            }
            $slug = self::clean_slug($slug);
            if ($DB->record_exists(self::BOOK, ['collectionid' => $collectionid, 'slug' => $slug])) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Slug de livre déjà utilisé');
            }
            $id = (int)$DB->insert_record(self::BOOK, (object)[
                'collectionid' => $collectionid, 'slug' => $slug,
                'title' => $title, 'subtitle' => $subtitle, 'published' => 0,
                'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        $tx->allow_commit();
        error_log('[CampusFR D2.1 editorial] book=' . $id . ' actor=' . $GLOBALS['USER']->id . ' event=save');
        return $id;
    }

    public static function page_count(int $bookid): int {
        global $DB;
        return $DB->count_records('local_cr_page', ['bookid' => $bookid]);
    }

    public static function slot_count(int $bookid): int {
        global $DB;
        return $DB->count_records('local_cr_slot', ['bookid' => $bookid]);
    }
}
