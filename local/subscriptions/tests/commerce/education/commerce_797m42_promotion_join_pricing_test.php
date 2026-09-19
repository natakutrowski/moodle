<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPriceRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricing;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

final class commerce_797m42_promotion_join_pricing_test extends advanced_testcase {
    /** @return array{0:object,1:CommerceProduct,2:CommercePedagogicalPromotion,3:object} */
    private function scenario(string $sku, int $now): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $product = $products->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $sku
        ));
        (new CommerceProductEntitlementRepository($DB, $hydrator, $products))
            ->replace_for_product($sku, [
                new CommerceProductEntitlementDefinition(
                    $sku,
                    'course_access',
                    'course:' . $course->id . ':full'
                ),
            ]);
        (new CommerceProductPriceRepository($DB, $hydrator, $products))->save(
            new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(28000, 'EUR'),
                true
            )
        );

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                strtolower($sku) . '-promo',
                'Promotion ' . $sku,
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                5,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            5,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-m42-' . strtolower($sku),
            'idempotencykey' => 'idem-m42-' . strtolower($sku),
            'purchasereference' => 'purchase-m42-' . strtolower($sku),
            'itemreference' => 'item-m42-' . strtolower($sku),
            'productsku' => $sku,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => $user->email,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return [$user, $product, $promotion, $course];
    }

    public function test_owner_price_is_explicit_and_never_falls_back_to_course_price(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$user] = $this->scenario('M42-NOPRICE', $now);

        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M42-NOPRICE', $now);
        self::assertTrue($eligibility->is_eligible());

        $pricing = CommercePedagogicalPromotionJoinPricingService::create($DB)
            ->resolve($eligibility, 'EUR');

        self::assertFalse($pricing->is_purchasable());
        self::assertSame(
            CommercePedagogicalPromotionJoinPricing::OWNER_PRICE_NOT_CONFIGURED,
            $pricing->get_reason()
        );
        self::assertNull($pricing->get_amount_minor());
    }

    public function test_configured_owner_price_is_resolved_in_minor_units(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$user, $product, $promotion] = $this->scenario('M42-OWNERPRICE', $now);

        $service = CommercePedagogicalPromotionJoinPricingService::create($DB);
        $saved = $service->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            10000,
            null,
            $now
        );
        self::assertNotNull($saved->get_id());
        self::assertSame(10000, $saved->get_amount_minor());

        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M42-OWNERPRICE', $now);
        $pricing = $service->resolve($eligibility, 'EUR');

        self::assertTrue($pricing->is_purchasable());
        self::assertNull($pricing->get_reason());
        self::assertSame('EUR', $pricing->get_currency());
        self::assertSame(10000, $pricing->get_amount_minor());
        self::assertSame($saved->get_id(), $pricing->get_price_id());
    }

    public function test_owner_price_can_only_use_an_active_product_currency(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [, $product, $promotion] = $this->scenario('M42-CURRENCY', $now);

        $this->expectException(\coding_exception::class);
        CommercePedagogicalPromotionJoinPricingService::create($DB)->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'RUB',
            1000000,
            null,
            $now
        );
    }

    public function test_clearing_price_disables_owner_join_purchase_without_changing_eligibility(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$user, $product, $promotion] = $this->scenario('M42-CLEAR', $now);

        $service = CommercePedagogicalPromotionJoinPricingService::create($DB);
        $service->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            9900,
            null,
            $now
        );
        $service->clear((int)$promotion->get_id(), (int)$product->get_id(), 'EUR');

        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M42-CLEAR', $now);
        self::assertTrue($eligibility->is_eligible());

        $pricing = $service->resolve($eligibility, 'EUR');
        self::assertFalse($pricing->is_purchasable());
        self::assertSame(
            CommercePedagogicalPromotionJoinPricing::OWNER_PRICE_NOT_CONFIGURED,
            $pricing->get_reason()
        );
    }

    public function test_unlinking_offer_removes_owner_prices(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [, $product, $promotion] = $this->scenario('M42-UNLINK', $now);

        CommercePedagogicalPromotionJoinPricingService::create($DB)->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            12000,
            null,
            $now
        );
        self::assertCount(
            1,
            CommercePedagogicalPromotionJoinPriceRepository::create($DB)->all_for_offer(
                (int)$promotion->get_id(),
                (int)$product->get_id()
            )
        );

        CommercePedagogicalPromotionOfferRepository::create($DB)->unlink(
            (int)$promotion->get_id(),
            (int)$product->get_id()
        );

        self::assertCount(
            0,
            CommercePedagogicalPromotionJoinPriceRepository::create($DB)->all_for_offer(
                (int)$promotion->get_id(),
                (int)$product->get_id()
            )
        );
    }

    public function test_storefront_read_model_exposes_owner_price_but_still_no_join_cta(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$user, $product, $promotion] = $this->scenario('M42-READ', $now);

        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M42-READ', $now);
        $service = CommercePedagogicalPromotionJoinPricingService::create($DB);
        $service->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            10000,
            null,
            $now
        );
        $pricing = $service->resolve($eligibility, 'EUR');

        $storefront = new CommerceStorefrontProduct(
            'M42-READ',
            'M4.2 Read Model',
            '',
            '',
            CommerceProductType::COURSE_ACCESS,
            [new CommerceStorefrontPrice('EUR', 28000, null, null, null, 42)],
            [],
            true,
            null,
            [],
            [],
            false,
            1000,
            [],
            'courses',
            [],
            [],
            true,
            null,
            [],
            (int)$product->get_id(),
            $eligibility,
            [$pricing]
        );

        $array = $storefront->to_array();
        self::assertSame(10000, $array['promotionjoinpricing'][0]['amountminor']);
        self::assertTrue($array['promotionjoinpricing'][0]['purchasable']);

        $presented = CommerceStorefrontPresenter::card($storefront, 'EUR');
        self::assertTrue($presented['promotionjoinpurchasable']);
        self::assertNotNull($presented['promotionjoinpriceformatted']);
        self::assertNotNull($presented['promotionjoinpriceid']);
        self::assertFalse($presented['canpurchase']);
        self::assertArrayNotHasKey('promotionjoinactionurl', $presented);
    }
}
