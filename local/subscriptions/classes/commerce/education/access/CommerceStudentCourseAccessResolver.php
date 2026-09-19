<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarResolver;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;

/**
 * Resolves individual pedagogical access without imposing course-global rules.
 *
 * Safety/backward compatibility:
 * - classic course => unrestricted;
 * - promotion course + no 7.97 relation => legacy_full/unrestricted;
 * - explicit progressive relation => calendar-filtered sections;
 * - once the calendar is complete => relation becomes lifetime_full.
 */
final class CommerceStudentCourseAccessResolver {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCourseAccessConfigurationRepository $courseconfig,
        private readonly CommerceStudentCourseAccessRepository $accessrepository,
        private readonly CommercePedagogicalCalendarResolver $calendarresolver,
        private readonly CommercePedagogicalParticipationRepository $participations
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            $db,
            CommerceCourseAccessConfigurationRepository::create($db),
            CommerceStudentCourseAccessRepository::create($db),
            CommercePedagogicalCalendarResolver::create($db),
            CommercePedagogicalParticipationRepository::create($db)
        );
    }

    public function resolve(
        int $courseid,
        int $userid,
        int $timestamp,
        ?int $actoruserid = null
    ): CommerceStudentCourseAccessDecision {
        $coursemode =
            $this->courseconfig->mode_for_course($courseid);

        if (
            $coursemode
            === CommerceCourseAccessMode::CLASSIC_IMMEDIATE
        ) {
            return new CommerceStudentCourseAccessDecision(
                CommerceStudentAccessProfile::LIFETIME_FULL,
                null,
                null
            );
        }

        $access =
            $this->accessrepository->find(
                $courseid,
                $userid
            );

        if ($access === null) {
            // Existing enrolments that predate 7.97 have no row. They must
            // keep the full course even after the course enters promotion mode.
            return new CommerceStudentCourseAccessDecision(
                CommerceStudentAccessProfile::LEGACY_FULL,
                null,
                null
            );
        }

        if (
            CommerceStudentAccessProfile::has_full_course_access(
                $access->get_profile()
            )
        ) {
            return new CommerceStudentCourseAccessDecision(
                $access->get_profile(),
                $access->get_promotion_id(),
                null
            );
        }

        $promotionid =
            $access->get_promotion_id();

        if ($promotionid === null) {
            throw new \coding_exception(
                'Progressive access relation has no pedagogical promotion.'
            );
        }

        // A refunded/cancelled 7.97 participation keeps the Commerce/Moodle
        // entitlement layer untouched, but no pedagogical lesson remains open.
        // Section 0 stays available through the decision DTO for orientation.
        if (
            $this->participations->has_history($promotionid, $userid)
            && !$this->participations->is_active($promotionid, $userid)
        ) {
            return new CommerceStudentCourseAccessDecision(
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                $promotionid,
                []
            );
        }

        if (
            $this->calendarresolver->is_complete_at(
                $promotionid,
                $timestamp
            )
        ) {
            $promoted =
                $this->accessrepository->save(
                    $access->with_profile(
                        CommerceStudentAccessProfile::LIFETIME_FULL,
                        $actoruserid,
                        $timestamp
                    )
                );

            return new CommerceStudentCourseAccessDecision(
                $promoted->get_profile(),
                $promotionid,
                null
            );
        }

        $unlockedsectionids = array_values(
            array_map(
                static fn(
                    CommercePedagogicalCalendarItem $item
                ): int => $item->get_item_id(),
                $this->calendarresolver->unlocked(
                    $promotionid,
                    $timestamp
                )
            )
        );

        return new CommerceStudentCourseAccessDecision(
            CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
            $promotionid,
            $unlockedsectionids
        );
    }
}
