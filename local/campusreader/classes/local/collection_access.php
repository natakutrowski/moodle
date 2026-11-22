<?php
// CampusFR Reader C1 — access policy. GPL-3.0-or-later.
namespace local_campusreader\local;

defined('MOODLE_INTERNAL') || die();

final class collection_access {
    public static function is_admin(): bool {
        return has_capability('moodle/site:config', \context_system::instance());
    }

    /** Only administrators can preview unpublished collections. */
    public static function can_view_collection(\stdClass $collection): bool {
        global $DB, $USER;
        if (self::is_admin()) {
            return true;
        }
        if (empty($collection->published) || empty($collection->courseid)
            || isguestuser($USER->id)) {
            return false;
        }
        $course = $DB->get_record('course', ['id' => $collection->courseid], 'id,visible');
        if (!$course || !$course->visible) {
            return false;
        }
        $context = \context_course::instance((int) $collection->courseid, IGNORE_MISSING);
        // Moodle's moodle/course:view is a viewing permission for users who may
        // access a course without enrolment. Students do not necessarily have it.
        // Require an ACTIVE enrolment instead; publication, visibility and
        // date-based restrictions remain enforced above and in can_read_book().
        return $context !== false && $context !== null
            && is_enrolled($context, $USER->id, '', true);
    }

    public static function unlocked(\stdClass $slot, ?int $now = null): bool {
        return (int) $slot->unlockat <= ($now ?? time());
    }

    /** Never expose book pages before BOTH the entitlement and the release date. */
    public static function can_read_book(\stdClass $book, \stdClass $collection, \stdClass $slot, ?int $now = null): bool {
        if (!calendar_policy::slot_active($collection, $slot)
            || (int) $book->collectionid !== (int) $collection->id
            || (int) ($slot->bookid ?? 0) !== (int) $book->id
            || (int) $slot->collectionid !== (int) $collection->id) {
            return false;
        }
        if (self::is_admin()) {
            return true;
        }
        return !empty($collection->published) && !empty($book->published)
            && self::unlocked($slot, $now) && self::can_view_collection($collection);
    }

    public static function require_collection(\stdClass $collection): void {
        if (!self::can_view_collection($collection)) {
            throw new \required_capability_exception(\context_system::instance(),
                'moodle/site:config', 'nopermissions', '');
        }
    }
}
