<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\course;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessResolver;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;

/**
 * Customer-facing course status shared by Mon Campus and Mes cours.
 *
 * The progress denominator deliberately uses every Moodle-visible activity
 * with completion enabled, even when the activity is currently unavailable to
 * a progressive student. This prevents future CampusFR releases from
 * disappearing from the denominator and artificially inflating progress.
 */
final class CommerceCustomerCourseLearningStatusService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceStudentCourseAccessResolver $accessresolver,
        private readonly CommercePedagogicalPromotionRepository $promotions,
        private readonly CommercePedagogicalCalendarRepository $calendar
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;

        return new self(
            $db,
            CommerceStudentCourseAccessResolver::create($db),
            CommercePedagogicalPromotionRepository::create($db),
            CommercePedagogicalCalendarRepository::create($db)
        );
    }

    /** @return array<string,mixed> */
    public function resolve(int $userid, int $courseid, ?int $now = null): array {
        if ($userid <= 0 || $courseid <= 0) {
            throw new \coding_exception('Customer course learning status requires valid user and course ids.');
        }

        $now ??= time();
        $decision = $this->accessresolver->resolve($courseid, $userid, $now);
        $profile = $decision->get_profile();
        $promotionid = $decision->get_promotion_id();
        $promotion = $promotionid !== null ? $this->promotions->get_by_id($promotionid) : null;
        if ($promotion !== null && $promotion->get_course_id() !== $courseid) {
            $promotion = null;
            $promotionid = null;
        }

        $calendaritems = $promotionid !== null ? $this->calendar->for_promotion($promotionid) : [];
        $next = null;
        $unlocked = 0;
        foreach ($calendaritems as $item) {
            if ($item->get_unlocks_at() <= $now) {
                $unlocked++;
                continue;
            }
            if ($next === null || $item->get_unlocks_at() < $next->get_unlocks_at()) {
                $next = $item;
            }
        }

        $nextsection = $next instanceof CommercePedagogicalCalendarItem
            ? $this->section_context($courseid, $next)
            : ['id' => null, 'number' => null, 'name' => ''];
        $progress = $this->completion_progress($userid, $courseid);
        $fullaccess = $decision->has_full_course_access();
        $progressive = $profile === CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE && !$fullaccess;

        return [
            'courseid' => $courseid,
            'profile' => $profile,
            'hasfullaccess' => $fullaccess,
            'isprogressive' => $progressive,
            'promotionid' => $promotionid,
            'haspromotion' => $promotion !== null,
            'promotionname' => $promotion !== null ? $promotion->get_name() : '',
            'promotionstatus' => $promotion !== null ? $promotion->get_status() : '',
            'promotionstartsat' => $promotion !== null ? $promotion->get_starts_at() : null,
            'promotionendsat' => $promotion !== null ? $promotion->get_ends_at() : null,
            'calendarcount' => count($calendaritems),
            'unlockedcalendarcount' => $unlocked,
            'allcalendarunlocked' => $calendaritems !== [] && $unlocked === count($calendaritems),
            'hasnextlesson' => $next !== null,
            'nextunlocksat' => $next?->get_unlocks_at(),
            'nextsectionid' => $nextsection['id'],
            'nextsectionnumber' => $nextsection['number'],
            'nextsectionname' => $nextsection['name'],
            'progressavailable' => $progress['available'],
            'progress' => $progress['progress'],
            'completedactivities' => $progress['done'],
            'totalactivities' => $progress['total'],
            'completed' => $progress['completed'],
        ];
    }

    /** @return array{available:bool,progress:?float,done:?int,total:?int,completed:bool} */
    private function completion_progress(int $userid, int $courseid): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        try {
            $course = get_course($courseid);
            $completion = new \completion_info($course);
            $modinfo = get_fast_modinfo($course, $userid);
            $done = 0;
            $total = 0;

            foreach ($modinfo->get_cms() as $cm) {
                // Use Moodle's teacher-visible flag, not uservisible. The
                // latter is false for future CampusFR sections and would make
                // progressive students appear further ahead than they are.
                if (empty($cm->visible) || !$completion->is_enabled($cm)) {
                    continue;
                }
                $total++;
                $data = $completion->get_data($cm, true, $userid);
                if ((int)($data->completionstate ?? 0) !== 0) {
                    $done++;
                }
            }

            if ($total <= 0) {
                return [
                    'available' => false,
                    'progress' => null,
                    'done' => null,
                    'total' => null,
                    'completed' => false,
                ];
            }

            $percentage = max(0.0, min(100.0, 100.0 * ($done / $total)));
            return [
                'available' => true,
                'progress' => $percentage,
                'done' => $done,
                'total' => $total,
                'completed' => $done >= $total,
            ];
        } catch (\Throwable) {
            return [
                'available' => false,
                'progress' => null,
                'done' => null,
                'total' => null,
                'completed' => false,
            ];
        }
    }

    /** @return array{id:?int,number:?int,name:string} */
    private function section_context(int $courseid, CommercePedagogicalCalendarItem $item): array {
        if ($item->get_item_type() !== CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION) {
            return ['id' => null, 'number' => null, 'name' => ''];
        }

        $section = $this->db->get_record(
            'course_sections',
            ['id' => $item->get_item_id(), 'course' => $courseid],
            'id,section,name',
            IGNORE_MISSING
        );
        if (!$section) {
            return ['id' => null, 'number' => null, 'name' => ''];
        }

        return [
            'id' => (int)$section->id,
            'number' => (int)$section->section,
            'name' => trim((string)($section->name ?? '')),
        ];
    }
}
