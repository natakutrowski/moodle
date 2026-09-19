<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPromotionJoinCartTransferService;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

/** M8.2H2: an abandoned direct promotion_join can move into the normal cart immediately. */
final class commerce_797m82h2_direct_to_cart_promotion_join_test extends advanced_testcase {
    /**
     * @return array{
     *   user:object,
     *   course:object,
     *   product:CommerceProduct,
     *   promotion:CommercePedagogicalPromotion,
     *   price:CommerceProductPrice
     * }
     */
    private function setup_owner_offer(
        string $sku,
        int $now,
        int $capacity = 2,
        ?int $salesopensat = null,
        ?int $salesclosesat = null
    ): array {
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

        $price = (new CommerceProductPriceRepository($DB, $hydrator, $products))
            ->save(new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(200, 'EUR'),
                true
            ));

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                strtolower($sku) . '-p1',
                $sku . ' P1',
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $salesopensat ?? ($now - HOURSECS),
                $salesclosesat ?? ($now + DAYSECS),
                $now + DAYSECS,
                null,
                $capacity,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            $capacity,
            null,
            $now
        );

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-h2-' . strtolower($sku),
            'idempotencykey' => 'idem-h2-' . strtolower($sku),
            'purchasereference' => 'purchase-h2-' . strtolower($sku),
            'itemreference' => 'item-h2-' . strtolower($sku),
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

        CommercePedagogicalPromotionJoinPricingService::create($DB)->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            100,
            null,
            $now
        );

        return compact('user', 'course', 'product', 'promotion', 'price');
    }

    public function test_cart_action_routes_promotion_join_add_through_direct_to_cart_bridge(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertStringContainsString(
            'CommerceDirectPromotionJoinCartTransferService',
            $source
        );
        self::assertMatchesRegularExpression(
            '/if \(\$operation === CommercePedagogicalPromotionJoinOperation::OPERATION\).*?->add_current_to_cart\(/s',
            $source
        );
    }

    public function test_direct_owner_join_moves_same_hold_into_normal_cart(): void {
        global $DB, $SESSION;

        $this->resetAfterTest(true);
        $now = time();
        $scenario = $this->setup_owner_offer('M82H2-MOVE', $now, 2);
        $userid = (int)$scenario['user']->id;
        $this->setUser($scenario['user']);
        $SESSION->local_subscriptions_commerce_carts = [];
        CommerceDirectPurchaseSession::clear();

        $carts = CommerceCartRuntimeFactory::create();
        $prepared = $carts->prepare_direct_product(
            $userid,
            'EUR',
            'fr',
            'M82H2-MOVE',
            (int)$scenario['price']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($prepared->has_changed());

        $directcart = $prepared->get_cart();
        $directitems = $directcart->get_items();
        self::assertCount(1, $directitems);
        $directitem = reset($directitems);
        self::assertNotFalse($directitem);

        CommerceDirectPurchaseSession::store(
            'EUR',
            'M82H2-MOVE',
            (int)$scenario['price']->get_id(),
            1,
            $directitem->get_metadata(),
            $directcart->get_uuid()
        );

        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $reservationrepo = CommercePedagogicalSeatReservationRepository::create($DB);
        $sourcehold = $reservationrepo->find(
            (int)$scenario['promotion']->get_id(),
            (int)$scenario['product']->get_id(),
            $directcart->get_uuid()
        );
        self::assertNotNull($sourcehold);
        $sourceholdid = $sourcehold->get_id();

        $normalcart = $carts->open($userid, 'EUR');
        $normaluuid = $normalcart->get_uuid();
        self::assertNotSame($directcart->get_uuid(), $normaluuid);
        self::assertCount(0, $normalcart->get_items());

        $remainingbefore = CommercePedagogicalCapacityService::create($DB)
            ->for_product('M82H2-MOVE', $now)
            ->get_remaining();
        self::assertSame(1, $remainingbefore);

        $result = (new CommerceDirectPromotionJoinCartTransferService(
            $carts,
            $reservations
        ))->add_current_to_cart(
            $userid,
            'EUR',
            'fr',
            'M82H2-MOVE',
            (int)$scenario['price']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now + 1
        );

        self::assertTrue($result->has_changed());
        self::assertSame($normaluuid, $result->get_cart()->get_uuid());
        self::assertCount(1, $result->get_cart()->get_items());
        self::assertNull(CommerceDirectPurchaseSession::current_any_currency());

        $oldhold = $reservationrepo->find(
            (int)$scenario['promotion']->get_id(),
            (int)$scenario['product']->get_id(),
            $directcart->get_uuid()
        );
        $newhold = $reservationrepo->find(
            (int)$scenario['promotion']->get_id(),
            (int)$scenario['product']->get_id(),
            $normaluuid
        );
        self::assertNull($oldhold);
        self::assertNotNull($newhold);
        self::assertSame($sourceholdid, $newhold->get_id());
        self::assertSame(
            $now + 1 + CommercePedagogicalSeatReservationService::DEFAULT_TTL,
            $newhold->get_expires_at()
        );
        self::assertSame($userid, $newhold->get_customer_id());
        self::assertSame(
            CommercePedagogicalSeatReservation::ACTIVE,
            $newhold->get_state()
        );
        self::assertSame(1, $DB->count_records(
            'local_subs_commerce_ped_resv',
            ['productid' => (int)$scenario['product']->get_id()]
        ));

        $remainingafter = CommercePedagogicalCapacityService::create($DB)
            ->for_product('M82H2-MOVE', $now + 1)
            ->get_remaining();
        self::assertSame($remainingbefore, $remainingafter);
    }

    public function test_direct_to_cart_refuses_to_spill_into_successor_promotion(): void {
        global $DB, $SESSION;

        $this->resetAfterTest(true);
        $now = time();
        $scenario = $this->setup_owner_offer(
            'M82H2-NOSPILL',
            $now,
            2,
            $now - HOURSECS,
            $now + 5
        );
        $userid = (int)$scenario['user']->id;
        $this->setUser($scenario['user']);
        $SESSION->local_subscriptions_commerce_carts = [];
        CommerceDirectPurchaseSession::clear();

        $successor = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm82h2-nospill-p2',
                'M82H2 NOSPILL P2',
                (int)$scenario['course']->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now + 6,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                2,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$successor->get_id(),
            (int)$scenario['product']->get_id(),
            2,
            null,
            $now
        );
        CommercePedagogicalPromotionJoinPricingService::create($DB)->configure(
            (int)$successor->get_id(),
            (int)$scenario['product']->get_id(),
            'EUR',
            100,
            null,
            $now
        );

        $carts = CommerceCartRuntimeFactory::create();
        $prepared = $carts->prepare_direct_product(
            $userid,
            'EUR',
            'fr',
            'M82H2-NOSPILL',
            (int)$scenario['price']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($prepared->has_changed());
        $directcart = $prepared->get_cart();
        $directitems = $directcart->get_items();
        $directitem = reset($directitems);
        self::assertNotFalse($directitem);
        self::assertSame(
            (int)$scenario['promotion']->get_id(),
            (int)$directitem->get_metadata()['promotion_join_promotion_id']
        );

        CommerceDirectPurchaseSession::store(
            'EUR',
            'M82H2-NOSPILL',
            (int)$scenario['price']->get_id(),
            1,
            $directitem->get_metadata(),
            $directcart->get_uuid()
        );

        $normalcart = $carts->open($userid, 'EUR');
        $normaluuid = $normalcart->get_uuid();

        $result = (new CommerceDirectPromotionJoinCartTransferService(
            $carts,
            CommercePedagogicalSeatReservationService::create($DB)
        ))->add_current_to_cart(
            $userid,
            'EUR',
            'fr',
            'M82H2-NOSPILL',
            (int)$scenario['price']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now + 10
        );

        self::assertFalse($result->has_changed());
        self::assertCount(1, $result->get_messages());
        self::assertSame(
            'promotion_join_context_changed',
            $result->get_messages()[0]->get_code()
        );
        self::assertNotNull(CommerceDirectPurchaseSession::current_any_currency());
        self::assertCount(0, $carts->open($userid, 'EUR')->get_items());

        $reservationrepo = CommercePedagogicalSeatReservationRepository::create($DB);
        $sourcehold = $reservationrepo->find(
            (int)$scenario['promotion']->get_id(),
            (int)$scenario['product']->get_id(),
            $directcart->get_uuid()
        );
        self::assertNotNull($sourcehold);
        self::assertTrue($sourcehold->is_active_at($now + 10));
        self::assertNull($reservationrepo->find(
            (int)$successor->get_id(),
            (int)$scenario['product']->get_id(),
            $normaluuid
        ));
    }

    public function test_product_scoped_transfer_cannot_adopt_another_customers_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $scenario = $this->setup_owner_offer('M82H2-OWNER', $now, 2);
        $other = $this->getDataGenerator()->create_user();
        $sourceuuid = str_repeat('a', 32);
        $targetuuid = str_repeat('b', 32);

        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $reservations->reserve(
            'M82H2-OWNER',
            $sourceuuid,
            (int)$scenario['user']->id,
            1,
            $now
        );

        self::assertFalse($reservations->transfer_product(
            'M82H2-OWNER',
            $sourceuuid,
            $targetuuid,
            (int)$other->id,
            $now + 1
        ));

        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        $source = $repo->find(
            (int)$scenario['promotion']->get_id(),
            (int)$scenario['product']->get_id(),
            $sourceuuid
        );
        self::assertNotNull($source);
        self::assertSame((int)$scenario['user']->id, $source->get_customer_id());
        self::assertNull($repo->find(
            (int)$scenario['promotion']->get_id(),
            (int)$scenario['product']->get_id(),
            $targetuuid
        ));
    }

    public function test_bridge_preserves_hold_instead_of_waiting_for_ttl(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/flow/'
            . 'CommerceDirectPromotionJoinCartTransferService.php'
        );

        self::assertStringContainsString('has_active_hold_for_customer(', $source);
        self::assertStringContainsString('prepare_direct_product(', $source);
        self::assertStringContainsString('promotion_join_promotion_id', $source);
        self::assertStringContainsString('promotion_join_context_changed', $source);
        self::assertStringContainsString('false,', $source);
        self::assertStringContainsString('$sourceuuid', $source);
        self::assertStringContainsString('transfer_product(', $source);
        self::assertStringContainsString('CommerceDirectPurchaseSession::clear();', $source);
    }

    public function test_reservation_service_exposes_product_scoped_same_customer_transfer(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/education/reservation/'
            . 'CommercePedagogicalSeatReservationService.php'
        );

        self::assertStringContainsString('public function transfer_product(', $source);
        self::assertStringContainsString('public function has_active_hold_for_customer(', $source);
        self::assertStringContainsString('$source->get_customer_id() !== $targetcustomerid', $source);
        self::assertStringContainsString('$this->with_promotion_lock(', $source);
    }
}
