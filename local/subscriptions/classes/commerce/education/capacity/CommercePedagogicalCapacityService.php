<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\capacity;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;

/**
 * Central read model for pedagogical Commerce availability.
 *
 * Active cart reservations are now first-class capacity consumers.
 */
final class CommercePedagogicalCapacityService {
    public const SALES_CLOSED = 'pedagogical_sales_closed';
    public const PROMOTION_FULL = 'pedagogical_promotion_full';
    public const OFFER_FULL = 'pedagogical_offer_full';
    public const GROUP_FULL = 'pedagogical_group_full';

    public function __construct(
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalParticipationRepository $participations,
        private readonly CommercePedagogicalGroupRepository $groups,
        private readonly CommercePedagogicalSeatReservationRepository $reservations
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommercePedagogicalParticipationRepository::create($db),
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalSeatReservationRepository::create($db)
        );
    }

    /**
     * @param ?string $excludedcartuuid Excludes only this cart's hold for
     *        this exact product. Other products held by the same cart still
     *        consume promotion-wide capacity.
     */
    public function for_product(
        string $productsku,
        int $now,
        ?string $excludedcartuuid = null
    ): CommercePedagogicalCapacitySnapshot {
        $sku = strtoupper(trim($productsku));
        $link = $this->offers->sale_link_for_product($sku, $now);

        if ($link === null) {
            return $this->unlinked_snapshot($sku);
        }

        return $this->for_link($sku, $link, $now, $excludedcartuuid);
    }

    /**
     * Capacity for an already pinned promotion/product pair.
     *
     * Paid fulfillment must never jump to a newer cohort merely because the
     * same stable Commerce SKU is reused by a later promotion.
     */
    public function for_promotion_product(
        string $productsku,
        int $promotionid,
        int $productid,
        int $now,
        ?string $excludedcartuuid = null
    ): CommercePedagogicalCapacitySnapshot {
        $sku = strtoupper(trim($productsku));
        $link = $this->offers->link_for_promotion_and_product(
            $promotionid,
            $productid
        );

        if ($link === null) {
            return $this->unlinked_snapshot($sku);
        }

        // The same product id is reused across cohorts, but a caller must not
        // be able to pair an unrelated SKU with an exact pedagogical offer.
        $matching = false;
        foreach ($this->offers->links_for_product($sku) as $candidate) {
            if (
                (int)$candidate['promotion']->get_id() === $promotionid
                && (int)$candidate['offer']->productid === $productid
            ) {
                $matching = true;
                break;
            }
        }
        if (!$matching) {
            return $this->unlinked_snapshot($sku);
        }

        return $this->for_link($sku, $link, $now, $excludedcartuuid);
    }

    /**
     * @param array{promotion: \local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion, offer: \stdClass} $link
     */
    private function for_link(
        string $sku,
        array $link,
        int $now,
        ?string $excludedcartuuid
    ): CommercePedagogicalCapacitySnapshot {
        $promotion = $link['promotion'];
        $offer = $link['offer'];
        $promotionid = (int)$promotion->get_id();
        $productid = (int)$offer->productid;

        $promotioncapacity = $promotion->get_capacity_total();
        $promotionoccupied =
            $this->participations->active_count_for_promotion(
                $promotionid
            );
        $promotionreserved =
            $this->reservations->active_quantity_for_promotion(
                $promotionid,
                $now,
                $excludedcartuuid,
                $excludedcartuuid !== null ? $productid : null
            );
        $promotionremaining = $this->remaining(
            $promotioncapacity,
            $promotionoccupied + $promotionreserved
        );

        $offercapacity = $offer->capacity !== null
            ? (int)$offer->capacity
            : null;
        $offeroocupied =
            $this->participations->active_count_for_offer(
                $promotionid,
                $productid
            );
        $offerreserved =
            $this->reservations->active_quantity_for_offer(
                $promotionid,
                $productid,
                $now,
                $excludedcartuuid
            );
        $offerremaining = $this->remaining(
            $offercapacity,
            $offeroocupied + $offerreserved
        );

        $configuration =
            $this->groups->get_configuration($promotionid);
        $groupsenabled = $configuration->is_enabled();
        $groupcapacity = null;
        $groupoccupied = 0;
        $groupremaining = null;

        if ($groupsenabled) {
            $groupcapacity = 0;
            $groupgrossremaining = 0;

            foreach (
                $this->groups->groups_for_product(
                    $promotionid,
                    $productid,
                    true
                ) as $group
            ) {
                $capacity = $configuration->get_group_size();
                $occupied = $this->groups->member_count(
                    (int)$group->get_id()
                );

                $groupcapacity += $capacity;
                $groupoccupied += $occupied;
                $groupgrossremaining += max(
                    0,
                    $capacity - $occupied
                );
            }

            // A reservation for this offer must eventually consume one seat
            // from one of its compatible groups.
            $groupremaining = max(
                0,
                $groupgrossremaining - $offerreserved
            );
        }

        $remaining = $this->minimum_limited_remaining([
            $promotionremaining,
            $offerremaining,
            $groupremaining,
        ]);

        $salesopen = $promotion->sales_are_open($now);
        $blockingreason = null;

        if (!$salesopen) {
            $blockingreason = self::SALES_CLOSED;
        } else if ($promotionremaining === 0) {
            $blockingreason = self::PROMOTION_FULL;
        } else if ($offerremaining === 0) {
            $blockingreason = self::OFFER_FULL;
        } else if ($groupsenabled && $groupremaining === 0) {
            $blockingreason = self::GROUP_FULL;
        }

        return new CommercePedagogicalCapacitySnapshot(
            $sku,
            true,
            $salesopen,
            $blockingreason === null,
            $blockingreason,
            $promotionid,
            $productid,
            $promotioncapacity,
            $promotionoccupied,
            $promotionreserved,
            $promotionremaining,
            $offercapacity,
            $offeroocupied,
            $offerreserved,
            $offerremaining,
            $groupsenabled,
            $groupcapacity,
            $groupoccupied,
            $groupremaining,
            $remaining
        );
    }

    private function unlinked_snapshot(
        string $sku
    ): CommercePedagogicalCapacitySnapshot {
        return new CommercePedagogicalCapacitySnapshot(
            $sku,
            false,
            true,
            true,
            null,
            null,
            null,
            null,
            0,
            0,
            null,
            null,
            0,
            0,
            null,
            false,
            null,
            0,
            null,
            null
        );
    }

    private function remaining(
        ?int $capacity,
        int $occupied
    ): ?int {
        if ($capacity === null) {
            return null;
        }

        return max(0, $capacity - $occupied);
    }

    /**
     * Minimum of all finite limits. null means no finite limit exists.
     *
     * @param array<int, ?int> $values
     */
    private function minimum_limited_remaining(array $values): ?int {
        $limited = array_values(array_filter(
            $values,
            static fn(?int $value): bool => $value !== null
        ));

        return $limited === [] ? null : min($limited);
    }
}
