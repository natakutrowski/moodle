<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\lifecycle;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

final class CommercePedagogicalParticipationRepository {
    private const TABLE = 'local_subs_commerce_ped_join';

    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function record_active(
        int $promotionid,
        int $courseid,
        int $userid,
        string $productsku,
        string $purchasereference,
        int $now
    ): void {
        $product = (new CommerceProductRepository($this->db, new CommerceCatalogHydrator()))
            ->find_by_sku(strtoupper(trim($productsku)));
        if ($product === null) {
            throw new \coding_exception('Unknown Commerce product for pedagogical participation.');
        }

        $existing = $this->db->get_record(self::TABLE, [
            'promotionid' => $promotionid,
            'userid' => $userid,
            'purchasereference' => trim($purchasereference),
        ], '*', IGNORE_MISSING);

        $record = (object)[
            'promotionid' => $promotionid,
            'courseid' => $courseid,
            'userid' => $userid,
            'productid' => (int)$product->get_id(),
            'purchasereference' => trim($purchasereference),
            'state' => self::ACTIVE,
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $record->timecreated = (int)$existing->timecreated;
            $this->db->update_record(self::TABLE, $record);
        } else {
            $record->timecreated = $now;
            $this->db->insert_record(self::TABLE, $record);
        }
    }

    public function active_count_for_promotion(int $promotionid): int {
        return $this->db->count_records(self::TABLE, [
            'promotionid' => $promotionid,
            'state' => self::ACTIVE,
        ]);
    }

    public function active_count_for_offer(int $promotionid, int $productid): int {
        return $this->db->count_records(self::TABLE, [
            'promotionid' => $promotionid,
            'productid' => $productid,
            'state' => self::ACTIVE,
        ]);
    }

    public function has_history(int $promotionid, int $userid): bool {
        return $this->db->record_exists(self::TABLE, [
            'promotionid' => $promotionid,
            'userid' => $userid,
        ]);
    }

    public function active_product_id_for_user(
        int $promotionid,
        int $userid
    ): ?int {
        $records = $this->db->get_records(
            self::TABLE,
            [
                'promotionid' => $promotionid,
                'userid' => $userid,
                'state' => self::ACTIVE,
            ],
            'id ASC',
            'id,productid'
        );

        if ($records === []) {
            return null;
        }

        $productids = array_values(array_unique(array_map(
            static fn(\stdClass $record): int => (int)$record->productid,
            array_values($records)
        )));

        if (count($productids) > 1) {
            throw new \coding_exception(
                'Participant has active purchases for several pedagogical offers.'
            );
        }

        return $productids[0];
    }

    public function is_active(int $promotionid, int $userid): bool {
        return $this->db->record_exists(self::TABLE, [
            'promotionid' => $promotionid,
            'userid' => $userid,
            'state' => self::ACTIVE,
        ]);
    }

    /** @return \stdClass[] */
    public function active_for_purchase(string $purchasereference): array {
        return array_values($this->db->get_records(self::TABLE, [
            'purchasereference' => trim($purchasereference),
            'state' => self::ACTIVE,
        ]));
    }

    public function change_state_for_purchase(
        string $purchasereference,
        string $state,
        int $now
    ): array {
        if (!in_array($state, [self::CANCELLED, self::REFUNDED], true)) {
            throw new \coding_exception('Unsupported pedagogical participation terminal state.');
        }

        $records = $this->active_for_purchase($purchasereference);
        foreach ($records as $record) {
            $record->state = $state;
            $record->timemodified = $now;
            $this->db->update_record(self::TABLE, $record);
        }
        return $records;
    }
}
