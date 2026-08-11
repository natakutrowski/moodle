<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\domain\CommercePromotion;

/** Moodle DB implementation of the Native Commerce promotion repository. */
final class MoodleCommercePromotionRepository implements CommercePromotionRepository {
    /** @var array<string, CommercePromotion|null> */
    private static array $codecache = [];
    private const TABLE = 'local_subs_commerce_promo';
    private const REDEMPTION_TABLE = 'local_subs_commerce_promouse';

    public function get_by_code(string $code): ?CommercePromotion {
        global $DB;
        $normalised = strtoupper(trim($code));
        if (array_key_exists($normalised, self::$codecache)) {
            return self::$codecache[$normalised];
        }
        $record = $DB->get_record(self::TABLE, ['code' => $normalised]);
        return self::$codecache[$normalised] = ($record ? $this->hydrate($record) : null);
    }

    public function get_by_id(int $id): ?CommercePromotion {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id]);
        return $record ? $this->hydrate($record) : null;
    }

    /** @return CommercePromotion[] */
    public function find_all(): array {
        global $DB;
        $records = $DB->get_records(self::TABLE, null, 'active DESC, priority DESC, id DESC');
        return array_map(fn(\stdClass $record): CommercePromotion => $this->hydrate($record), array_values($records));
    }

    public function delete(int $id): void {
        global $DB;
        $DB->delete_records(self::REDEMPTION_TABLE, ['promotionid' => $id]);
        $DB->delete_records(self::TABLE, ['id' => $id]);
        self::invalidate_cache();
    }

    public static function invalidate_cache(): void {
        self::$codecache = [];
    }

    public function find_automatic(int $at): array {
        global $DB;
        $sql = 'automatic = 1 AND active = 1 AND (startsat IS NULL OR startsat <= :at1)'
            . ' AND (endsat IS NULL OR endsat > :at2)';
        $records = $DB->get_records_select(self::TABLE, $sql, ['at1' => $at, 'at2' => $at], 'priority DESC, id ASC');
        return array_map(fn(\stdClass $record): CommercePromotion => $this->hydrate($record), array_values($records));
    }

    public function save(CommercePromotion $promotion): CommercePromotion {
        global $DB;
        $now = time();
        $record = $this->to_record($promotion);
        $record->timemodified = $now;
        if ($promotion->get_id() === null) {
            $record->timecreated = $now;
            $record->id = $DB->insert_record(self::TABLE, $record);
        } else {
            $record->id = $promotion->get_id();
            $DB->update_record(self::TABLE, $record);
        }
        self::invalidate_cache();
        return $this->hydrate($DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST));
    }

    public function count_redemptions(int $promotionid, ?int $userid = null): int {
        global $DB;
        $conditions = ['promotionid' => $promotionid, 'status' => 'applied'];
        if ($userid !== null) {
            $conditions['userid'] = $userid;
        }
        return $DB->count_records(self::REDEMPTION_TABLE, $conditions);
    }

    private function to_record(CommercePromotion $promotion): \stdClass {
        return (object)[
            'name' => $promotion->get_name(),
            'code' => $promotion->get_code(),
            'discounttype' => $promotion->get_discount_type(),
            'discountvalue' => $promotion->get_discount_value(),
            'currency' => $promotion->get_currency(),
            'minimumcartminor' => $promotion->get_minimum_cart_minor(),
            'startsat' => $promotion->get_starts_at(),
            'endsat' => $promotion->get_ends_at(),
            'active' => (int)$promotion->is_active(),
            'automatic' => (int)$promotion->is_automatic(),
            'stackable' => (int)$promotion->is_stackable(),
            'priority' => $promotion->get_priority(),
            'globalusagelimit' => $promotion->get_global_usage_limit(),
            'userusagelimit' => $promotion->get_user_usage_limit(),
            'productskusjson' => json_encode(array_values($promotion->get_product_skus()), JSON_THROW_ON_ERROR),
            'producttypesjson' => json_encode(array_values($promotion->get_product_types()), JSON_THROW_ON_ERROR),
            'metadatajson' => json_encode($promotion->get_metadata(), JSON_THROW_ON_ERROR),
        ];
    }

    private function hydrate(\stdClass $record): CommercePromotion {
        return new CommercePromotion(
            (int)$record->id,
            (string)$record->name,
            $record->code === null ? null : (string)$record->code,
            (string)$record->discounttype,
            (int)$record->discountvalue,
            $record->currency === null ? null : (string)$record->currency,
            (int)$record->minimumcartminor,
            $record->startsat === null ? null : (int)$record->startsat,
            $record->endsat === null ? null : (int)$record->endsat,
            (bool)$record->active,
            (bool)$record->automatic,
            (bool)$record->stackable,
            (int)$record->priority,
            $record->globalusagelimit === null ? null : (int)$record->globalusagelimit,
            $record->userusagelimit === null ? null : (int)$record->userusagelimit,
            $this->decode_list($record->productskusjson),
            $this->decode_list($record->producttypesjson),
            $this->decode_map($record->metadatajson)
        );
    }

    private function decode_list(?string $json): array {
        $decoded = $json ? json_decode($json, true) : [];
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    private function decode_map(?string $json): array {
        $decoded = $json ? json_decode($json, true) : [];
        return is_array($decoded) ? $decoded : [];
    }
}
