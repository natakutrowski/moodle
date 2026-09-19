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
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSeatReservationCoordinator;
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

/** M8.3-B resistance contract for abandoning and resuming checkout. */
final class commerce_797m83b_checkout_abandon_resume_test extends advanced_testcase {
    public function test_checkout_abandon_and_resume_keep_same_hold_and_anchored_deadline(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M83B-RESUME', $now);
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

        $snapshot = $s['carts']->snapshot($userid, 'EUR', 'fr', $now);
        self::assertCount(2, $snapshot->get_items());
        self::assertSame(400, $snapshot->get_totals()->get_total()->get_amount_minor());

        $cartuuid = $snapshot->get_cart()->get_uuid();
        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        $before = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($before);
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $before->get_state());
        self::assertNull($before->get_checkout_started_at());
        self::assertNull($before->get_payment_started_at());
        self::assertSame(
            $now + CommercePedagogicalSeatReservationService::DEFAULT_TTL,
            $before->get_expires_at()
        );

        $holdid = $before->get_id();
        $expiry = $before->get_expires_at();
        $coordinator = new CommerceCheckoutSeatReservationCoordinator($s['reservations']);

        // First checkout entry: anchor the checkout phase once. The original
        // 15-minute cart hold is longer than the 10-minute checkout minimum,
        // so entering checkout must not shorten it.
        $coordinator->extend_snapshot(
            $snapshot,
            $now + MINSECS,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );

        $firstcheckout = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($firstcheckout);
        self::assertSame($holdid, $firstcheckout->get_id());
        self::assertSame($cartuuid, $firstcheckout->get_cart_uuid());
        self::assertSame($userid, $firstcheckout->get_customer_id());
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $firstcheckout->get_state());
        self::assertSame($now + MINSECS, $firstcheckout->get_checkout_started_at());
        self::assertNull($firstcheckout->get_payment_started_at());
        self::assertSame($expiry, $firstcheckout->get_expires_at());

        // Simulate leaving checkout, returning to the cart, then re-entering
        // checkout several minutes later. The same hold must be reused and the
        // first checkout anchor must not slide forward on every resume.
        $resumed = $s['carts']->snapshot($userid, 'EUR', 'fr', $now + 5 * MINSECS);
        self::assertCount(2, $resumed->get_items());
        self::assertSame(400, $resumed->get_totals()->get_total()->get_amount_minor());

        $coordinator->extend_snapshot(
            $resumed,
            $now + 5 * MINSECS,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );

        $afterresume = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($afterresume);
        self::assertSame($holdid, $afterresume->get_id());
        self::assertSame($cartuuid, $afterresume->get_cart_uuid());
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $afterresume->get_state());
        self::assertSame($now + MINSECS, $afterresume->get_checkout_started_at());
        self::assertNull($afterresume->get_payment_started_at());
        self::assertSame($expiry, $afterresume->get_expires_at());
        self::assertSame(
            2,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $now + 5 * MINSECS)
                ->get_remaining()
        );
    }

    public function test_expired_started_checkout_resume_does_not_silently_reacquire_seat(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M83B-EXPIRED', $now);
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

        $snapshot = $s['carts']->snapshot($userid, 'EUR', 'fr', $now);
        $cartuuid = $snapshot->get_cart()->get_uuid();
        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        $before = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($before);
        $holdid = $before->get_id();

        $coordinator = new CommerceCheckoutSeatReservationCoordinator($s['reservations']);
        $coordinator->extend_snapshot(
            $snapshot,
            $now + MINSECS,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );

        $started = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($started);
        self::assertSame($holdid, $started->get_id());
        self::assertSame($now + MINSECS, $started->get_checkout_started_at());
        $expiredat = $started->get_expires_at();

        $late = $s['carts']->snapshot($userid, 'EUR', 'fr', $expiredat + 1);
        $coordinator->extend_snapshot(
            $late,
            $expiredat + 1,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );

        $expired = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($expired);
        self::assertSame($holdid, $expired->get_id());
        self::assertSame($cartuuid, $expired->get_cart_uuid());
        self::assertSame(CommercePedagogicalSeatReservation::EXPIRED, $expired->get_state());
        self::assertSame($now + MINSECS, $expired->get_checkout_started_at());
        self::assertSame($expiredat, $expired->get_expires_at());
        self::assertSame(
            3,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $expiredat + 1)
                ->get_remaining()
        );

        // A second checkout reload remains expired; it must not create another
        // reservation row or silently consume the released capacity again.
        $coordinator->extend_snapshot(
            $late,
            $expiredat + 2,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );
        $still = $repo->find_for_cart_product($cartuuid, (int)$s['joinproduct']->get_id());
        self::assertNotNull($still);
        self::assertSame($holdid, $still->get_id());
        self::assertSame(CommercePedagogicalSeatReservation::EXPIRED, $still->get_state());
        self::assertSame(
            3,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($s['joinsku'], $expiredat + 2)
                ->get_remaining()
        );
    }

    /**
     * @return array{
     *   user:object,
     *   joinsku:string,
     *   digitalsku:string,
     *   joinproduct:CommerceProduct,
     *   joinprice:CommerceProductPrice,
     *   digitalprice:CommerceProductPrice,
     *   carts:CommerceCartService,
     *   reservations:CommercePedagogicalSeatReservationService
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
            'carts',
            'reservations'
        );
    }
}
