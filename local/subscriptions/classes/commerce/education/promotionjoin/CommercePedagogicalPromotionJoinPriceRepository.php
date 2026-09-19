<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

/** Persistence for owner-only promotion-join prices. */
final class CommercePedagogicalPromotionJoinPriceRepository {
    private const TABLE = 'local_subs_commerce_ped_jprice';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function find(int $promotionid, int $productid, string $currency): ?CommercePedagogicalPromotionJoinPrice {
        $record = $this->db->get_record(self::TABLE, [
            'promotionid' => $promotionid,
            'productid' => $productid,
            'currency' => strtoupper(trim($currency)),
        ], '*', IGNORE_MISSING);

        return $record ? $this->hydrate($record) : null;
    }

    /** @return CommercePedagogicalPromotionJoinPrice[] */
    public function all_for_offer(int $promotionid, int $productid): array {
        $records = $this->db->get_records(
            self::TABLE,
            ['promotionid' => $promotionid, 'productid' => $productid],
            'currency ASC, id ASC'
        );
        return array_values(array_map(fn(\stdClass $record): CommercePedagogicalPromotionJoinPrice => $this->hydrate($record), $records));
    }

    public function save(
        int $promotionid,
        int $productid,
        string $currency,
        int $amountminor,
        ?int $actoruserid,
        int $now
    ): CommercePedagogicalPromotionJoinPrice {
        $currency = strtoupper(trim($currency));
        $price = new CommercePedagogicalPromotionJoinPrice(
            $promotionid,
            $productid,
            $currency,
            $amountminor
        );

        if (!$this->db->record_exists('local_subs_commerce_ped_offer', [
            'promotionid' => $promotionid,
            'productid' => $productid,
        ])) {
            throw new \coding_exception('Promotion join price requires an existing pedagogical offer link.');
        }

        $existing = $this->db->get_record(self::TABLE, [
            'promotionid' => $promotionid,
            'productid' => $productid,
            'currency' => $currency,
        ], '*', IGNORE_MISSING);

        $record = (object)[
            'promotionid' => $promotionid,
            'productid' => $productid,
            'currency' => $currency,
            'amountminor' => $amountminor,
            'modifiedby' => $actoruserid,
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $record->createdby = $existing->createdby;
            $record->timecreated = (int)$existing->timecreated;
            $this->db->update_record(self::TABLE, $record);
            $id = (int)$existing->id;
        } else {
            $record->createdby = $actoruserid;
            $record->timecreated = $now;
            $id = (int)$this->db->insert_record(self::TABLE, $record);
        }

        return new CommercePedagogicalPromotionJoinPrice(
            $price->get_promotion_id(),
            $price->get_product_id(),
            $price->get_currency(),
            $price->get_amount_minor(),
            $id
        );
    }

    public function delete(int $promotionid, int $productid, string $currency): void {
        $this->db->delete_records(self::TABLE, [
            'promotionid' => $promotionid,
            'productid' => $productid,
            'currency' => strtoupper(trim($currency)),
        ]);
    }

    public function delete_for_offer(int $promotionid, int $productid): void {
        $this->db->delete_records(self::TABLE, [
            'promotionid' => $promotionid,
            'productid' => $productid,
        ]);
    }

    private function hydrate(\stdClass $record): CommercePedagogicalPromotionJoinPrice {
        return new CommercePedagogicalPromotionJoinPrice(
            (int)$record->promotionid,
            (int)$record->productid,
            (string)$record->currency,
            (int)$record->amountminor,
            (int)$record->id
        );
    }
}
