<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotion;

defined('MOODLE_INTERNAL') || die();

final class CommercePedagogicalPromotionRepository {
    private const TABLE = 'local_subs_commerce_ped_promo';

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

    /** @return CommercePedagogicalPromotion[] */
    public function all(): array {
        return array_values(
            array_map(
                fn(\stdClass $record): CommercePedagogicalPromotion =>
                    $this->hydrate($record),
                $this->db->get_records(
                    self::TABLE,
                    null,
                    'startsat DESC, id DESC'
                )
            )
        );
    }

    public function get_by_id(int $id): ?CommercePedagogicalPromotion {
        $record = $this->db->get_record(
            self::TABLE,
            ['id' => $id],
            '*',
            IGNORE_MISSING
        );

        return $record ? $this->hydrate($record) : null;
    }

    public function get_by_key(string $promotionkey): ?CommercePedagogicalPromotion {
        $record = $this->db->get_record(
            self::TABLE,
            ['promotionkey' => trim($promotionkey)],
            '*',
            IGNORE_MISSING
        );

        return $record ? $this->hydrate($record) : null;
    }

    public function save(
        CommercePedagogicalPromotion $promotion
    ): CommercePedagogicalPromotion {
        if (!$this->db->record_exists(
            'course',
            ['id' => $promotion->get_course_id()]
        )) {
            throw new \coding_exception(
                'Cannot persist a pedagogical promotion for an unknown Moodle course.'
            );
        }

        $record = $this->to_record($promotion);

        if ($promotion->get_id() === null) {
            $record->id = $this->db->insert_record(
                self::TABLE,
                $record
            );
        } else {
            $record->id = $promotion->get_id();
            $this->db->update_record(
                self::TABLE,
                $record
            );
        }

        return $this->get_by_id((int)$record->id)
            ?? throw new \coding_exception(
                'Pedagogical promotion could not be reloaded after save.'
            );
    }

    public function delete(int $id): void {
        $this->db->delete_records(
            self::TABLE,
            ['id' => $id]
        );
    }

    private function hydrate(
        \stdClass $record
    ): CommercePedagogicalPromotion {
        return new CommercePedagogicalPromotion(
            (int)$record->id,
            (string)$record->promotionkey,
            (string)$record->name,
            (int)$record->courseid,
            (string)$record->status,
            (bool)$record->published,
            $record->salesopensat !== null
                ? (int)$record->salesopensat
                : null,
            $record->salesclosesat !== null
                ? (int)$record->salesclosesat
                : null,
            $record->startsat !== null
                ? (int)$record->startsat
                : null,
            $record->endsat !== null
                ? (int)$record->endsat
                : null,
            $record->capacitytotal !== null
                ? (int)$record->capacitytotal
                : null,
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

    private function to_record(
        CommercePedagogicalPromotion $promotion
    ): \stdClass {
        return (object)[
            'promotionkey' => $promotion->get_promotion_key(),
            'name' => $promotion->get_name(),
            'courseid' => $promotion->get_course_id(),
            'status' => $promotion->get_status(),
            'published' => $promotion->is_published() ? 1 : 0,
            'salesopensat' => $promotion->get_sales_opens_at(),
            'salesclosesat' => $promotion->get_sales_closes_at(),
            'startsat' => $promotion->get_starts_at(),
            'endsat' => $promotion->get_ends_at(),
            'capacitytotal' => $promotion->get_capacity_total(),
            'createdby' => $promotion->get_created_by(),
            'modifiedby' => $promotion->get_modified_by(),
            'timecreated' => $promotion->get_time_created(),
            'timemodified' => $promotion->get_time_modified(),
        ];
    }
}
