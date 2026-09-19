<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\catalog\MoodleCommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\ownership\MoodleCommerceCartOwnershipGateway;
use local_subscriptions\commerce\cart\policy\CommerceQuantityPolicyResolver;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/** M8.3-A resistance contract for removing lines from mixed carts. */
final class commerce_797m83a_mixed_cart_removal_test extends advanced_testcase {
    public function test_removing_digital_keeps_join_hold_and_recalculates_cart(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M83A-DIGITAL-FIRST', $now);
        $userid = (int)$s['user']->id;

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            $s['joinsku'],
            (int)$s['joinprice']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($joined->has_changed());

        $digital = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            $s['digitalsku'],
            (int)$s['digitalprice']->get_id(),
            1,
            [],
            $now
        );
        self::assertTrue($digital->has_changed());

        $cartuuid = $digital->get_cart()->get_uuid();
        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        $hold = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($hold);
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $hold->get_state());
        $holdid = $hold->get_id();

        $before = $s['carts']->snapshot($userid, 'EUR', 'fr', $now);
        self::assertCount(2, $before->get_items());
        self::assertSame(400, $before->get_totals()->get_total()->get_amount_minor());
        self::assertSame(
            2,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $now)
                ->get_remaining()
        );

        $removed = $s['carts']->remove_product(
            $userid,
            'EUR',
            $s['digitalsku'],
            (int)$s['digitalprice']->get_id()
        );
        self::assertTrue($removed->has_changed());

        $after = $s['carts']->snapshot($userid, 'EUR', 'fr', $now + 1);
        self::assertCount(1, $after->get_items());
        self::assertSame(100, $after->get_totals()->get_total()->get_amount_minor());
        self::assertSame($s['joinsku'], $after->get_items()[0]->get_item()->get_product_sku());
        self::assertSame(
            'promotion_join',
            $after->get_items()[0]->get_item()->get_metadata()['operation'] ?? null
        );

        $holdafterdigital = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($holdafterdigital);
        self::assertSame($holdid, $holdafterdigital->get_id());
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $holdafterdigital->get_state());
        self::assertSame(
            2,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $now + 1)
                ->get_remaining()
        );

        $removedjoin = $s['carts']->remove_product(
            $userid,
            'EUR',
            $s['joinsku'],
            (int)$s['joinprice']->get_id()
        );
        self::assertTrue($removedjoin->has_changed());

        $empty = $s['carts']->snapshot($userid, 'EUR', 'fr', $now + 2);
        self::assertCount(0, $empty->get_items());
        self::assertSame(0, $empty->get_totals()->get_total()->get_amount_minor());

        $released = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($released);
        self::assertSame($holdid, $released->get_id());
        self::assertSame(CommercePedagogicalSeatReservation::RELEASED, $released->get_state());
        self::assertSame(
            3,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $now + 2)
                ->get_remaining()
        );
    }

    public function test_removing_join_releases_hold_without_mutating_digital_line(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M83A-JOIN-FIRST', $now);
        $userid = (int)$s['user']->id;

        $joined = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            $s['joinsku'],
            (int)$s['joinprice']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($joined->has_changed());

        $digital = $s['carts']->add_product(
            $userid,
            'EUR',
            'fr',
            $s['digitalsku'],
            (int)$s['digitalprice']->get_id(),
            1,
            [],
            $now
        );
        self::assertTrue($digital->has_changed());
        $cartuuid = $digital->get_cart()->get_uuid();

        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        $hold = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($hold);
        $holdid = $hold->get_id();

        $removedjoin = $s['carts']->remove_product(
            $userid,
            'EUR',
            $s['joinsku'],
            (int)$s['joinprice']->get_id()
        );
        self::assertTrue($removedjoin->has_changed());

        $after = $s['carts']->snapshot($userid, 'EUR', 'fr', $now + 1);
        self::assertCount(1, $after->get_items());
        self::assertSame(300, $after->get_totals()->get_total()->get_amount_minor());
        self::assertSame($s['digitalsku'], $after->get_items()[0]->get_item()->get_product_sku());

        $released = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($released);
        self::assertSame($holdid, $released->get_id());
        self::assertSame(CommercePedagogicalSeatReservation::RELEASED, $released->get_state());
        self::assertSame(
            3,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $now + 1)
                ->get_remaining()
        );

        $removeddigital = $s['carts']->remove_product(
            $userid,
            'EUR',
            $s['digitalsku'],
            (int)$s['digitalprice']->get_id()
        );
        self::assertTrue($removeddigital->has_changed());

        $empty = $s['carts']->snapshot($userid, 'EUR', 'fr', $now + 2);
        self::assertCount(0, $empty->get_items());
        self::assertSame(0, $empty->get_totals()->get_total()->get_amount_minor());
    }

    /**
     * @return array{
     *   user:object,
     *   joinsku:string,
     *   digitalsku:string,
     *   joinproduct:CommerceProduct,
     *   joinprice:CommerceProductPrice,
     *   digitalprice:CommerceProductPrice,
     *   carts:CommerceCartService
     * }
     */
    private function scenario(string $prefix, int $now): array {
        global $DB;

        $joinsku = $prefix . '-JOIN';
        $digitalsku = $prefix . '-DIGITAL';
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $joinproduct = $products->save(new CommerceProduct(
            $joinsku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $joinsku
        ));
        $products->save(new CommerceProduct(
            $digitalsku,
            CommerceProductType::DIGITAL_DOWNLOAD,
            CommerceProductStatus::ACTIVE,
            $digitalsku
        ));

        (new CommerceProductEntitlementRepository($DB, $hydrator, $products))
            ->replace_for_product($joinsku, [
                new CommerceProductEntitlementDefinition(
                    $joinsku,
                    'course_access',
                    'course:' . $course->id . ':full'
                ),
            ]);

        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        $joinprice = $prices->save(new CommerceProductPrice(
            $joinsku,
            CommerceMoney::from_minor(200, 'EUR'),
            true
        ));
        $digitalprice = $prices->save(new CommerceProductPrice(
            $digitalsku,
            CommerceMoney::from_minor(300, 'EUR'),
            true
        ));

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                strtolower($prefix) . '-promo',
                'Promotion ' . $prefix,
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                3,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$joinproduct->get_id(),
            3,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-' . strtolower($prefix),
            'idempotencykey' => 'idem-' . strtolower($prefix),
            'purchasereference' => 'purchase-' . strtolower($prefix),
            'itemreference' => 'item-' . strtolower($prefix),
            'productsku' => $joinsku,
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

        $joinpricing = CommercePedagogicalPromotionJoinPricingService::create($DB);
        $joinpricing->configure(
            (int)$promotion->get_id(),
            (int)$joinproduct->get_id(),
            'EUR',
            100,
            null,
            $now
        );

        $catalog = new MoodleCommerceCartCatalogGateway(
            $products,
            $prices,
            new CommerceProductTranslationRepository($DB, $hydrator, $products),
            new CommerceQuantityPolicyResolver()
        );
        $ownership = new MoodleCommerceCartOwnershipGateway(
            new CommerceStorefrontOwnershipResolver($DB)
        );
        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $calculator = new CommerceCartCalculator(
            $catalog,
            null,
            null,
            null,
            null,
            $eligibility,
            $joinpricing
        );
        $carts = new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            $calculator,
            $catalog,
            $ownership,
            null,
            null,
            null,
            $reservations,
            $eligibility,
            $joinpricing
        );

        return compact(
            'user',
            'joinsku',
            'digitalsku',
            'joinproduct',
            'joinprice',
            'digitalprice',
            'carts'
        );
    }
}
