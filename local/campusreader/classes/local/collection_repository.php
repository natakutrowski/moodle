<?php
// CampusFR Reader C1 — canonical DB source for collection books/pages. GPL-3.0-or-later.
namespace local_campusreader\local;

defined('MOODLE_INTERNAL') || die();

final class collection_repository {
    public static function collection(string $slug): \stdClass {
        global $DB;
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug)) {
            throw new \moodle_exception('invalidparameter');
        }
        return $DB->get_record('local_cr_collection', ['slug' => $slug], '*', MUST_EXIST);
    }

    /** @return \stdClass[] */
    public static function slots(int $collectionid): array {
        global $DB;
        $collection = $DB->get_record('local_cr_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $limit = calendar_policy::active_count($collection);
        if ($limit === 0) {
            return [];
        }
        return array_values($DB->get_records_select('local_cr_slot',
            'collectionid = :cid AND position >= 1 AND position <= :maxpos',
            ['cid' => $collectionid, 'maxpos' => $limit], 'position ASC'));
    }

    public static function book_bundle(int $bookid): array {
        global $DB;
        $book = $DB->get_record('local_cr_book', ['id' => $bookid], '*', MUST_EXIST);
        $collection = $DB->get_record('local_cr_collection', ['id' => $book->collectionid], '*', MUST_EXIST);
        // C1: one canonical calendar slot per book; reject ambiguous duplicates.
        $slots = $DB->get_records('local_cr_slot',
            ['collectionid' => $collection->id, 'bookid' => $bookid], 'position ASC', '*', 0, 2);
        if (count($slots) !== 1) {
            throw new \moodle_exception('invalidparameter');
        }
        $slot = reset($slots);
        return [$book, $collection, $slot];
    }

    public static function pages(int $bookid): array {
        global $DB;
        $rows = array_values($DB->get_records('local_cr_page', ['bookid' => $bookid], 'pageindex ASC'));
        if (count($rows) < 2 || count($rows) > 100) {
            throw new \moodle_exception('invalidparameter');
        }
        $result = [];
        foreach ($rows as $index => $row) {
            if ((int) $row->pageindex !== $index || !in_array($row->kind,
                ['cover', 'text', 'image', 'mixed', 'back'], true)) {
                throw new \moodle_exception('invalidparameter');
            }
            $paragraphs = json_decode($row->paragraphsjson, true, 16);
            if (!is_array($paragraphs) || !array_is_list($paragraphs)
                || !$paragraphs || count($paragraphs) > 8) {
                throw new \moodle_exception('invalidparameter');
            }
            foreach ($paragraphs as $paragraph) {
                if (!is_string($paragraph) || $paragraph === '' || strlen($paragraph) > 5000) {
                    throw new \moodle_exception('invalidparameter');
                }
            }
            $result[] = [
                'kind' => $row->kind,
                'title' => $row->title,
                'paragraphs' => $paragraphs,
                'imagefile' => $row->imagefile,
                'imagealt' => $row->imagealt,
            ];
        }
        if ($result[0]['kind'] !== 'cover' || end($result)['kind'] !== 'back') {
            throw new \moodle_exception('invalidparameter');
        }
        return $result;
    }
}
