<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\calendar;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;

final class CommercePedagogicalCalendarRepository {
    private const TABLE = 'local_subs_commerce_ped_cal';

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /** @return CommercePedagogicalCalendarItem[] */
    public function for_promotion(int $promotionid): array {
        $records = $this->db->get_records(
            self::TABLE,
            ['promotionid' => $promotionid],
            'position ASC, unlocksat ASC, id ASC'
        );

        return array_values(
            array_map(
                fn(\stdClass $record): CommercePedagogicalCalendarItem =>
                    $this->hydrate($record),
                $records
            )
        );
    }

    public function save(
        CommercePedagogicalCalendarItem $item
    ): CommercePedagogicalCalendarItem {
        $promotion =
            CommercePedagogicalPromotionRepository::create($this->db)
                ->get_by_id($item->get_promotion_id());

        if ($promotion === null) {
            throw new \coding_exception(
                'Cannot save a calendar item for an unknown pedagogical promotion.'
            );
        }

        if (
            $item->get_item_type()
            === CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION
        ) {
            $section = $this->db->get_record(
                'course_sections',
                ['id' => $item->get_item_id()],
                'id,course',
                IGNORE_MISSING
            );

            if (
                !$section
                || (int)$section->course
                    !== $promotion->get_course_id()
            ) {
                throw new \coding_exception(
                    'Calendar course section must belong to the promotion course.'
                );
            }
        }

        $record = (object)[
            'promotionid' => $item->get_promotion_id(),
            'itemtype' => $item->get_item_type(),
            'itemid' => $item->get_item_id(),
            'position' => $item->get_position(),
            'unlocksat' => $item->get_unlocks_at(),
            'createdby' => $item->get_created_by(),
            'modifiedby' => $item->get_modified_by(),
            'timecreated' => $item->get_time_created(),
            'timemodified' => $item->get_time_modified(),
        ];

        if ($item->get_id() === null) {
            $record->id = $this->db->insert_record(
                self::TABLE,
                $record
            );
        } else {
            $record->id = $item->get_id();
            $this->db->update_record(
                self::TABLE,
                $record
            );
        }

        $saved = $this->db->get_record(
            self::TABLE,
            ['id' => (int)$record->id],
            '*',
            MUST_EXIST
        );

        return $this->hydrate($saved);
    }

    public function delete(int $id): void {
        $this->db->delete_records(
            self::TABLE,
            ['id' => $id]
        );
    }

    private function hydrate(
        \stdClass $record
    ): CommercePedagogicalCalendarItem {
        return new CommercePedagogicalCalendarItem(
            (int)$record->id,
            (int)$record->promotionid,
            (string)$record->itemtype,
            (int)$record->itemid,
            (int)$record->position,
            (int)$record->unlocksat,
            $record->createdby !== null
                ? (int)$record->createdby
                : null,
            $record->modifiedby !== null
                ? (int)$record->modifiedby
                : null,
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }
}
