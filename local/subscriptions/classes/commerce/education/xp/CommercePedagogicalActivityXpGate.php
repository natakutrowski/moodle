<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Fail-closed link from a Level Up reason/context to a released course section.
 * Never inspects, changes or resets the personal Level Up ledger.
 */
final class CommercePedagogicalActivityXpGate {
    public function __construct(private readonly \moodle_database $db) {}

    /**
     * Resolve eligible Moodle course-module id, or null if ambiguous/closed.
     */
    public function eligible_cmid(
        int $promotionid,
        int $courseid,
        ?int $sourcecontextid,
        int $timeearned
    ): ?int {
        if ($promotionid <= 0 || $courseid <= 0 || !$sourcecontextid || $timeearned <= 0) {
            return null;
        }
        $context = \context::instance_by_id($sourcecontextid, IGNORE_MISSING);
        if (!$context || (int)$context->contextlevel !== CONTEXT_MODULE) {
            return null;  // Never infer the lesson from a system/course context.
        }
        $cmid = (int)$context->instanceid;
        $cm = $this->db->get_record('course_modules', ['id' => $cmid], 'id,course,section', IGNORE_MISSING);
        if (!$cm || (int)$cm->course !== $courseid || (int)$cm->section <= 0) {
            return null;
        }
        // CampusFR schedules MAJOR sections only. In Moodle 5, an H5P or
        // another activity may belong to a delegated mod_subsection whose
        // course_sections.id is NOT present in the promotion calendar.
        // Reuse the same hierarchy as the pedagogical access resolver.
        $parents = \local_subscriptions\commerce\education\calendar\CommercePedagogicalSectionHierarchy::create(
            $this->db
        )->subsection_parents($courseid);
        $sectionid = (int)$cm->section;
        $visited = [];
        while ($sectionid > 0 && !isset($visited[$sectionid])) {
            $visited[$sectionid] = true;
            $release = $this->db->get_record('local_subs_commerce_ped_cal', [
                'promotionid' => $promotionid,
                'itemtype' => 'course_section',
                'itemid' => $sectionid,
            ], 'id,unlocksat', IGNORE_MISSING);
            if ($release) {
                // A known but locked ancestor must NEVER fall through to
                // another calendar entry. Fail closed until its unlock time.
                return (int)$release->unlocksat <= $timeearned ? $cmid : null;
            }
            $sectionid = $parents[$sectionid] ?? 0;
        }
        return null; // No scheduled ancestor, or invalid/cyclic hierarchy.
    }

    /**
     * Level Up may supply reasons from block_xp or local_xp namespaces.
     * Persisted XP logs store their short names (e.g. activity_completion).
     * Every promotion award path must use that exact same value.
     */
    public static function normalise_reason_name(string $classname): string {
        $lastslash = strrpos($classname, '\\');
        $short = $lastslash === false ? $classname : substr($classname, $lastslash + 1);
        if (str_ends_with($short, '_reason')) {
            $short = substr($short, 0, -7);
        }
        return $short;
    }

    /**
     * Same idempotency identity for personal-positive and historical replay.
     * One promotion award per (course module, XP rule, reason type).
     */
    public static function source_key(int $cmid, int $ruleid, string $reason, ?string $subtype): string {
        if ($cmid <= 0 || $ruleid <= 0 || trim($reason) === '') {
            throw new \coding_exception('Invalid activity XP source identity.');
        }
        return 'activity-v1:' . hash('sha256', implode("\n", [
            (string)$cmid, (string)$ruleid, trim($reason), (string)$subtype,
        ]));
    }
}
