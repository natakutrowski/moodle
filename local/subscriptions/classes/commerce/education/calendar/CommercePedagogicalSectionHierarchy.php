<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\calendar;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle 5 delegated subsections inherit the pedagogical unlock of their parent.
 *
 * Only top-level sections need an explicit entry in the promotion calendar.
 * Moodle section availability (including existing non-CampusFR restrictions)
 * remains enforced on every section separately.
 */
final class CommercePedagogicalSectionHierarchy {
    /** Request-scope memoisation: availability can resolve many sections per page. */
    private static array $requestparentcache = [];

    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * @return array<int,int> Delegated subsection ID => parent course section ID.
     */
    public function subsection_parents(int $courseid): array {
        if ($courseid <= 0) {
            throw new \coding_exception('A valid course is required for section hierarchy.');
        }
        // Tests re-use course IDs across DB resets; never cache their fixtures.
        $cacheable = !defined('PHPUNIT_TEST');
        if ($cacheable && isset(self::$requestparentcache[$courseid])) {
            return self::$requestparentcache[$courseid];
        }

        // Moodle 5 records the delegated section's subsection instance in itemid.
        // Its mod_subsection course module is physically located in the parent.
        $rows = $this->db->get_records_sql(
            "SELECT child.id, cm.section AS parentid
               FROM {course_sections} child
               JOIN {modules} m ON m.name = :modulename
               JOIN {course_modules} cm ON cm.module = m.id
                                        AND cm.instance = child.itemid
                                        AND cm.course = child.course
              WHERE child.course = :courseid
                AND child.component = :component",
            [
                'modulename' => 'subsection',
                'courseid' => $courseid,
                'component' => 'mod_subsection',
            ]
        );

        $parents = [];
        foreach ($rows as $row) {
            $child = (int)$row->id;
            $parent = (int)$row->parentid;
            if ($child > 0 && $parent > 0 && $child !== $parent) {
                $parents[$child] = $parent;
            }
        }
        if ($cacheable) {
            self::$requestparentcache[$courseid] = $parents;
        }
        return $parents;
    }

    /**
     * @param int[] $unlockedsectionids Top-level sections open at this time.
     * @return int[] Includes the delegated subsections of those open sections.
     */
    public function with_unlocked_subsections(int $courseid, array $unlockedsectionids): array {
        $unlocked = array_fill_keys(array_map('intval', $unlockedsectionids), true);
        $parents = $this->subsection_parents($courseid);
        // Iterate to accommodate deeper delegated nesting if Moodle adds it.
        do {
            $changed = false;
            foreach ($parents as $child => $parent) {
                if (isset($unlocked[$parent]) && !isset($unlocked[$child])) {
                    $unlocked[$child] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        return array_map('intval', array_keys($unlocked));
    }
}
