<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarResolver;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;

/**
 * Persists lifetime access once a promotion calendar is fully unlocked.
 *
 * The individual resolver remains the immediate safety net. This service is
 * the background counterpart so dormant students do not remain progressive
 * forever merely because they have not revisited the course.
 */
final class CommerceProgressiveAccessFinalizer {
    private const ACCESS_TABLE = 'local_subs_commerce_ped_access';

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceStudentCourseAccessRepository $access,
        private readonly CommercePedagogicalCalendarResolver $calendar,
        private readonly CommercePedagogicalParticipationRepository $participations
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            $db,
            CommerceStudentCourseAccessRepository::create($db),
            CommercePedagogicalCalendarResolver::create($db),
            CommercePedagogicalParticipationRepository::create($db)
        );
    }

    /**
     * Finalise all eligible progressive relations.
     *
     * Only users with an active pedagogical participation are promoted.
     * Cancelled/refunded historical relations are intentionally left alone.
     */
    public function finalise(int $now): int {
        $rows = $this->db->get_records(
            self::ACCESS_TABLE,
            ['profile' => CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE],
            'promotionid ASC, id ASC',
            'id,courseid,userid,promotionid'
        );

        $complete = [];
        $count = 0;

        foreach ($rows as $row) {
            $promotionid = $row->promotionid !== null ? (int)$row->promotionid : 0;
            if ($promotionid <= 0) {
                continue;
            }

            if (!array_key_exists($promotionid, $complete)) {
                $complete[$promotionid] = $this->calendar->is_complete_at($promotionid, $now);
            }
            if (!$complete[$promotionid]) {
                continue;
            }

            $userid = (int)$row->userid;
            if (!$this->participations->is_active($promotionid, $userid)) {
                continue;
            }

            $current = $this->access->find((int)$row->courseid, $userid);
            if (
                $current === null
                || $current->get_promotion_id() !== $promotionid
                || $current->get_profile() !== CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE
            ) {
                continue;
            }

            $this->access->save($current->with_profile(
                CommerceStudentAccessProfile::LIFETIME_FULL,
                null,
                $now
            ));
            $count++;
        }

        return $count;
    }
}
