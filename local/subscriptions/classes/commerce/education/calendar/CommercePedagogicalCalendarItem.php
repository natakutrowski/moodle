<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\calendar;

defined('MOODLE_INTERNAL') || die();

/**
 * One explicit pedagogical release milestone for a promotion.
 *
 * 7.97D stores one unlock timestamp per pedagogical item. It deliberately
 * contains no assumption such as "two lessons per week".
 */
final class CommercePedagogicalCalendarItem {
    public const TYPE_COURSE_SECTION = 'course_section';

    public function __construct(
        private readonly ?int $id,
        private readonly int $promotionid,
        private readonly string $itemtype,
        private readonly int $itemid,
        private readonly int $position,
        private readonly int $unlocksat,
        private readonly ?int $createdby,
        private readonly ?int $modifiedby,
        private readonly int $timecreated,
        private readonly int $timemodified
    ) {
        if ($promotionid <= 0) {
            throw new \coding_exception(
                'Pedagogical calendar item requires a promotion id.'
            );
        }
        if ($itemtype !== self::TYPE_COURSE_SECTION) {
            throw new \coding_exception(
                'Unsupported pedagogical calendar item type: ' . $itemtype
            );
        }
        if ($itemid <= 0) {
            throw new \coding_exception(
                'Pedagogical calendar item requires a valid item id.'
            );
        }
        if ($position < 0) {
            throw new \coding_exception(
                'Pedagogical calendar item position cannot be negative.'
            );
        }
        if ($unlocksat <= 0) {
            throw new \coding_exception(
                'Pedagogical calendar item requires an unlock timestamp.'
            );
        }
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_promotion_id(): int {
        return $this->promotionid;
    }

    public function get_item_type(): string {
        return $this->itemtype;
    }

    public function get_item_id(): int {
        return $this->itemid;
    }

    public function get_position(): int {
        return $this->position;
    }

    public function get_unlocks_at(): int {
        return $this->unlocksat;
    }

    public function get_created_by(): ?int {
        return $this->createdby;
    }

    public function get_modified_by(): ?int {
        return $this->modifiedby;
    }

    public function get_time_created(): int {
        return $this->timecreated;
    }

    public function get_time_modified(): int {
        return $this->timemodified;
    }

    public function is_unlocked_at(int $timestamp): bool {
        return $timestamp >= $this->unlocksat;
    }
}
