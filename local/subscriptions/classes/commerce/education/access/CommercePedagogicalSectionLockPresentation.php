<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;

/**
 * Read-only presentation helper for an individually locked course section.
 *
 * Access control remains entirely in CommerceStudentCourseAccessResolver.
 * This helper only exposes the matching future unlock timestamp to the UI.
 */
final class CommercePedagogicalSectionLockPresentation {
    public function __construct(
        private readonly CommerceStudentCourseAccessRepository $accessrepository,
        private readonly CommercePedagogicalCalendarRepository $calendarrepository
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            CommerceStudentCourseAccessRepository::create($db),
            CommercePedagogicalCalendarRepository::create($db)
        );
    }

    public function unlock_at(
        int $courseid,
        int $userid,
        int $sectionid,
        int $now
    ): ?int {
        $access = $this->accessrepository->find(
            $courseid,
            $userid
        );

        if (
            $access === null
            || $access->get_profile()
                !== CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE
            || $access->get_promotion_id() === null
        ) {
            return null;
        }

        foreach (
            $this->calendarrepository->for_promotion(
                $access->get_promotion_id()
            ) as $item
        ) {
            if (
                $item->get_item_type()
                    === CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION
                && $item->get_item_id() === $sectionid
                && $item->get_unlocks_at() > $now
            ) {
                return $item->get_unlocks_at();
            }
        }

        return null;
    }
}
