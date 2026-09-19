<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Keeps Moodle section availability wired to the CampusFR individual resolver.
 *
 * The Moodle restriction is only a technical hook. The actual decision stays
 * individual and is resolved by CommerceStudentCourseAccessResolver:
 * - classic courses are unrestricted;
 * - legacy_full/lifetime_full users are unrestricted;
 * - promotion_progressive users follow their own promotion calendar.
 */
final class CommercePedagogicalAvailabilitySynchronizer {
    private const CONDITION_TYPE = 'campusfr';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCourseAccessConfigurationRepository $courseconfig
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            $db,
            CommerceCourseAccessConfigurationRepository::create($db)
        );
    }

    public function sync_all_promotion_courses(): void {
        foreach ($this->courseconfig->promotion_course_ids() as $courseid) {
            $this->sync_course($courseid);
        }
    }

    public function sync_course(int $courseid): void {
        if ($courseid <= 0) {
            throw new \coding_exception(
                'CampusFR availability synchronisation requires a valid course id.'
            );
        }

        $promotionmode =
            $this->courseconfig->mode_for_course($courseid)
            === CommerceCourseAccessMode::PROMOTION;

        $sections = $this->db->get_records(
            'course_sections',
            ['course' => $courseid],
            'section ASC',
            'id,course,section,availability'
        );

        $changed = false;

        foreach ($sections as $section) {
            if ((int)$section->section === 0) {
                continue;
            }

            $current = $section->availability !== null
                ? (string)$section->availability
                : null;

            $next = $promotionmode
                ? self::add_campusfr_condition($current)
                : self::remove_campusfr_condition($current);

            if ($next === $current) {
                continue;
            }

            $this->db->set_field(
                'course_sections',
                'availability',
                $next,
                ['id' => (int)$section->id]
            );
            $changed = true;
        }

        if ($changed) {
            // course/lib.php is included from method scope here. Core expects
            // $CFG to exist in the including scope, so import the Moodle
            // global explicitly before requiring the library.
            global $CFG;
            require_once($CFG->dirroot . '/course/lib.php');
            rebuild_course_cache($courseid, true);
        }
    }

    public static function add_campusfr_condition(?string $availability): string {
        $campusfr = (object)['type' => self::CONDITION_TYPE];

        if ($availability === null || trim($availability) === '') {
            return json_encode(
                \core_availability\tree::get_root_json(
                    [$campusfr],
                    \core_availability\tree::OP_AND,
                    true
                )
            );
        }

        $root = json_decode($availability);
        if (!is_object($root)) {
            throw new \coding_exception(
                'Invalid Moodle availability JSON while adding CampusFR restriction.'
            );
        }

        if (self::contains_campusfr_condition($root)) {
            return $availability;
        }

        // The standard root AND form supports adding another condition without
        // changing existing logical or display semantics.
        if (
            isset($root->op)
            && $root->op === \core_availability\tree::OP_AND
            && isset($root->c)
            && is_array($root->c)
            && isset($root->showc)
            && is_array($root->showc)
        ) {
            $root->c[] = $campusfr;
            $root->showc[] = true;

            return json_encode($root);
        }

        // For OR/NOT roots, preserve the existing logic as a nested branch and
        // AND the CampusFR condition with it. Root-only display metadata is
        // transferred to the wrapper where possible.
        $existing = clone $root;
        $show = true;
        if (isset($existing->show) && is_bool($existing->show)) {
            $show = $existing->show;
        }
        unset($existing->show, $existing->showc);

        return json_encode(
            \core_availability\tree::get_root_json(
                [$existing, $campusfr],
                \core_availability\tree::OP_AND,
                [$show, true]
            )
        );
    }

    public static function remove_campusfr_condition(?string $availability): ?string {
        if ($availability === null || trim($availability) === '') {
            return $availability;
        }

        $root = json_decode($availability);
        if (
            !is_object($root)
            || !isset($root->c)
            || !is_array($root->c)
        ) {
            return $availability;
        }

        $children = [];
        $shows = [];

        foreach ($root->c as $index => $child) {
            if (
                is_object($child)
                && isset($child->type)
                && $child->type === self::CONDITION_TYPE
            ) {
                continue;
            }

            $children[] = $child;
            if (
                isset($root->showc)
                && is_array($root->showc)
                && array_key_exists($index, $root->showc)
            ) {
                $shows[] = (bool)$root->showc[$index];
            }
        }

        if (count($children) === count($root->c)) {
            return $availability;
        }

        if ($children === []) {
            return null;
        }

        $root->c = $children;
        if (isset($root->showc) && is_array($root->showc)) {
            $root->showc = $shows;
        }

        return json_encode($root);
    }

    private static function contains_campusfr_condition(object $node): bool {
        if (
            isset($node->type)
            && $node->type === self::CONDITION_TYPE
        ) {
            return true;
        }

        if (!isset($node->c) || !is_array($node->c)) {
            return false;
        }

        foreach ($node->c as $child) {
            if (
                is_object($child)
                && self::contains_campusfr_condition($child)
            ) {
                return true;
            }
        }

        return false;
    }
}
