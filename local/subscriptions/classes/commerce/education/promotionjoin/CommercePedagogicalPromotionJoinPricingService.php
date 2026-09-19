<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotionjoin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/** Authoritative owner-only promotion-join pricing contract. */
final class CommercePedagogicalPromotionJoinPricingService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalPromotionJoinPriceRepository $joinprices,
        private readonly CommerceProductPriceRepository $productprices
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($db, $hydrator);

        return new self(
            $db,
            CommercePedagogicalPromotionJoinPriceRepository::create($db),
            new CommerceProductPriceRepository($db, $hydrator, $products)
        );
    }

    public function resolve(
        CommercePedagogicalPromotionJoinEligibility $eligibility,
        string $currency
    ): CommercePedagogicalPromotionJoinPricing {
        $currency = strtoupper(trim($currency));
        $context = $eligibility->get_context();

        if (!$eligibility->is_eligible()) {
            return CommercePedagogicalPromotionJoinPricing::denied(
                $eligibility->get_reason() ?? CommercePedagogicalPromotionJoinEligibility::NO_PROMOTION,
                $currency
            );
        }
        if ($context === null || !$this->currency_is_active_for_product($context->get_product_sku(), $currency)) {
            return CommercePedagogicalPromotionJoinPricing::denied(
                CommercePedagogicalPromotionJoinPricing::CURRENCY_NOT_AVAILABLE,
                $currency
            );
        }

        $price = $this->joinprices->find(
            $context->get_promotion_id(),
            $context->get_product_id(),
            $currency
        );
        if ($price === null) {
            return CommercePedagogicalPromotionJoinPricing::denied(
                CommercePedagogicalPromotionJoinPricing::OWNER_PRICE_NOT_CONFIGURED,
                $currency
            );
        }

        return CommercePedagogicalPromotionJoinPricing::allowed($price);
    }

    public function configure(
        int $promotionid,
        int $productid,
        string $currency,
        int $amountminor,
        ?int $actoruserid,
        int $now
    ): CommercePedagogicalPromotionJoinPrice {
        $currency = strtoupper(trim($currency));
        $offer = $this->db->get_record(
            'local_subs_commerce_ped_offer',
            ['promotionid' => $promotionid, 'productid' => $productid],
            'id,promotionid,productid',
            IGNORE_MISSING
        );
        if (!$offer) {
            throw new \coding_exception('Promotion join pricing requires an existing pedagogical offer link.');
        }

        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['id' => $productid],
            'id,sku',
            MUST_EXIST
        );
        if (!$this->currency_is_active_for_product((string)$product->sku, $currency)) {
            throw new \coding_exception('Promotion join pricing currency must exist as an active product currency.');
        }

        return $this->joinprices->save(
            $promotionid,
            $productid,
            $currency,
            $amountminor,
            $actoruserid,
            $now
        );
    }

    public function clear(int $promotionid, int $productid, string $currency): void {
        $this->joinprices->delete($promotionid, $productid, $currency);
    }

    private function currency_is_active_for_product(string $sku, string $currency): bool {
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            return false;
        }
        foreach ($this->productprices->find_by_product_sku($sku, true) as $price) {
            if ($price->get_currency() === $currency) {
                return true;
            }
        }
        return false;
    }
}
