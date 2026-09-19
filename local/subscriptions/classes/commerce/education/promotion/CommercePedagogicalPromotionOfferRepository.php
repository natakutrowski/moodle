<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPriceRepository;

/**
 * Links pedagogical promotions to Native Commerce products/formulas.
 *
 * The commercial source is deliberately the stable product identity (SKU).
 * Prices/providers remain payment concerns and do not decide pedagogy.
 */
final class CommercePedagogicalPromotionOfferRepository {
    private const TABLE = 'local_subs_commerce_ped_offer';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function link(
        int $promotionid,
        int $productid,
        ?int $capacity,
        ?int $actoruserid,
        int $now
    ): void {
        if ($capacity !== null && $capacity <= 0) {
            throw new \coding_exception('Per-offer capacity must be positive or unlimited.');
        }

        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($promotionid);
        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion.');
        }

        $products = new CommerceProductRepository($this->db, new CommerceCatalogHydrator());
        if ($products->find_by_id($productid) === null) {
            throw new \coding_exception('Unknown Native Commerce product.');
        }

        $existing = $this->db->get_record(
            self::TABLE,
            ['promotionid' => $promotionid, 'productid' => $productid],
            '*',
            IGNORE_MISSING
        );

        $record = (object)[
            'promotionid' => $promotionid,
            'productid' => $productid,
            'capacity' => $capacity,
            'modifiedby' => $actoruserid,
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $record->createdby = $existing->createdby;
            $record->timecreated = (int)$existing->timecreated;
            $this->db->update_record(self::TABLE, $record);
        } else {
            $record->createdby = $actoruserid;
            $record->timecreated = $now;
            $this->db->insert_record(self::TABLE, $record);
        }
    }

    public function unlink(int $promotionid, int $productid): void {
        CommercePedagogicalPromotionJoinPriceRepository::create($this->db)
            ->delete_for_offer($promotionid, $productid);
        $this->db->delete_records(self::TABLE, [
            'promotionid' => $promotionid,
            'productid' => $productid,
        ]);
    }

    /** @return \stdClass[] */
    public function links_for_promotion(int $promotionid): array {
        return array_values($this->db->get_records_sql(
            "SELECT l.*, p.sku, p.name AS productname, p.status AS productstatus
               FROM {" . self::TABLE . "} l
               JOIN {local_subs_commerce_product} p ON p.id = l.productid
              WHERE l.promotionid = :promotionid
           ORDER BY p.name ASC, p.id ASC",
            ['promotionid' => $promotionid]
        ));
    }

    /**
     * @return array<int,array{promotion: CommercePedagogicalPromotion, offer: \stdClass}>
     */
    public function links_for_product(string $productsku): array {
        $records = $this->db->get_records_sql(
            "SELECT l.*, p.sku
               FROM {" . self::TABLE . "} l
               JOIN {local_subs_commerce_product} p ON p.id = l.productid
               JOIN {local_subs_commerce_ped_promo} pp ON pp.id = l.promotionid
              WHERE p.sku = :sku
                AND pp.status <> :archived
           ORDER BY pp.id DESC",
            [
                'sku' => strtoupper(trim($productsku)),
                'archived' => CommercePedagogicalPromotionStatus::ARCHIVED,
            ]
        );

        $promotions = CommercePedagogicalPromotionRepository::create($this->db);
        $links = [];
        foreach ($records as $record) {
            $promotion = $promotions->get_by_id((int)$record->promotionid);
            if ($promotion !== null) {
                $links[] = [
                    'promotion' => $promotion,
                    'offer' => $record,
                ];
            }
        }
        return $links;
    }

    public function has_link_for_product(string $productsku): bool {
        return $this->links_for_product($productsku) !== [];
    }

    /**
     * Resolve the pedagogical promotion that currently owns the public sale of
     * a Commerce product.
     *
     * The same stable product may be reused by successive cohorts. Exactly one
     * cohort may have an open sales window at a time. When none is open, the
     * newest linked non-archived cohort is returned so public surfaces can
     * still render a closed/upcoming pedagogical state instead of losing the
     * relationship entirely.
     *
     * @return array{promotion: CommercePedagogicalPromotion, offer: \stdClass}|null
     */
    public function sale_link_for_product(
        string $productsku,
        ?int $now = null
    ): ?array {
        $links = $this->links_for_product($productsku);
        if ($links === []) {
            return null;
        }
        if (count($links) === 1) {
            return $links[0];
        }

        $now = $now ?? time();
        $open = array_values(array_filter(
            $links,
            static fn(array $link): bool =>
                $link['promotion']->sales_are_open($now)
        ));

        if (count($open) === 1) {
            return $open[0];
        }
        if (count($open) > 1) {
            throw new \coding_exception(
                'Several pedagogical promotions have sales open for the same Commerce product.'
            );
        }

        // links_for_product() is newest promotion first. Returning the newest
        // closed/upcoming cohort keeps storefront/showroom state deterministic
        // while still refusing genuinely overlapping open windows above.
        return $links[0];
    }

    /**
     * Exact immutable link lookup used once a reservation has pinned a cart to
     * a specific cohort.
     *
     * @return array{promotion: CommercePedagogicalPromotion, offer: \stdClass}|null
     */
    public function link_for_promotion_and_product(
        int $promotionid,
        int $productid
    ): ?array {
        $offer = $this->db->get_record(
            self::TABLE,
            [
                'promotionid' => $promotionid,
                'productid' => $productid,
            ],
            '*',
            IGNORE_MISSING
        );
        if ($offer === false) {
            return null;
        }

        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($promotionid);
        return $promotion === null ? null : [
            'promotion' => $promotion,
            'offer' => $offer,
        ];
    }

    public function promotion_for_product_and_course(
        string $productsku,
        int $courseid,
        ?int $now = null
    ): ?CommercePedagogicalPromotion {
        $links = array_values(array_filter(
            $this->links_for_product($productsku),
            static fn(array $link): bool =>
                $link['promotion']->get_course_id() === $courseid
        ));

        if ($links === []) {
            return null;
        }
        if (count($links) === 1) {
            return $links[0]['promotion'];
        }

        $now = $now ?? time();
        $open = array_values(array_filter(
            $links,
            static fn(array $link): bool =>
                $link['promotion']->sales_are_open($now)
        ));
        if (count($open) === 1) {
            return $open[0]['promotion'];
        }
        if (count($open) > 1) {
            throw new \coding_exception(
                'Several pedagogical promotions have sales open for the same Commerce product and course.'
            );
        }

        return $links[0]['promotion'];
    }
}
