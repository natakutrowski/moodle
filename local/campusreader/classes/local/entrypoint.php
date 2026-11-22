<?php
// CampusFR C3.1 — Read-only navigation adapter for purchased Reader collections.
// GPL-3.0-or-later. No commerce writes, no entitlement bypass.
declare(strict_types=1);
namespace local_campusreader\local;

defined('MOODLE_INTERNAL') || die();

final class entrypoint {
    /**
     * Return one published, explicitly enrolled, visible Reader collection for a support course.
     * Fail closed if more than one collection is published for that course.
     *
     * @return array{courseid:int,slug:string,title:string,url:string}|null
     */
    public static function for_course(int $courseid): ?array {
        global $DB, $USER;
        if ($courseid <= 0 || !isloggedin() || isguestuser() || empty($USER->id)
            || !empty($USER->suspended)) {
            return null;
        }
        $course = $DB->get_record('course', ['id' => $courseid], 'id,visible');
        if (!$course || !(int)$course->visible) {
            return null;
        }
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context || !is_enrolled($context, (int)$USER->id, '', true)) {
            return null;
        }
        $collections = array_values($DB->get_records('local_cr_collection',
            ['courseid' => $courseid, 'published' => 1], 'id ASC', '*', 0, 2));
        if (count($collections) !== 1) {
            return null;
        }
        $collection = $collections[0];
        $slug = (string)$collection->slug;
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug)) {
            return null;
        }
        return [
            'courseid' => $courseid,
            'slug' => $slug,
            'title' => format_string((string)$collection->title),
            'url' => (new \moodle_url('/local/campusreader/library.php', ['collection' => $slug]))->out(false),
        ];
    }

    /** @return list<array{courseid:int,slug:string,title:string,url:string}> */
    public static function for_current_user(): array {
        global $DB, $USER;
        if (!isloggedin() || isguestuser() || empty($USER->id) || !empty($USER->suspended)) {
            return [];
        }
        // No unpublished collection metadata is returned. A fixed ceiling avoids heavy dashboard queries.
        $rows = $DB->get_records('local_cr_collection', ['published' => 1], 'id ASC', 'id,courseid', 0, 100);
        $result = [];
        $seen = [];
        foreach ($rows as $row) {
            $id = (int)$row->courseid;
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $entry = self::for_course($id);
            if ($entry !== null) {
                $result[] = $entry;
            }
        }
        return $result;
    }
}
