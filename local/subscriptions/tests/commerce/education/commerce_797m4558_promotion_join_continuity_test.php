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
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseCurrencySwitchService;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSeatReservationCoordinator;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchasePersister;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinGrant;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationPurchaseLifecycle;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestStatus;

/** M4.5.5-M4.5.8 continuity contract for promotion_join. */
final class commerce_797m4558_promotion_join_continuity_test extends advanced_testcase {
    /**
     * @return array{
     *   user:object,
     *   course:object,
     *   product:CommerceProduct,
     *   promotion:CommercePedagogicalPromotion,
     *   eurprice:CommerceProductPrice,
     *   rubprice:?CommerceProductPrice
     * }
     */
    private function setup_owner_offer(
        string $sku,
        int $now,
        int $capacity = 1,
        bool $withrub = false,
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

        $prices = new CommerceProductPriceRepository($DB, $hydrator, $products);
        $eurprice = $prices->save(new CommerceProductPrice(
            $sku,
            CommerceMoney::from_minor(200, 'EUR'),
            true
        ));
        $rubprice = $withrub
            ? $prices->save(new CommerceProductPrice(
                $sku,
                CommerceMoney::from_minor(20000, 'RUB'),
                true
            ))
            : null;

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

        // Real Commerce ownership: promotion_join must never rely on bare
        // Moodle enrolment for owner eligibility.
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-m4558-' . strtolower($sku),
            'idempotencykey' => 'idem-m4558-' . strtolower($sku),
            'purchasereference' => 'purchase-m4558-' . strtolower($sku),
            'itemreference' => 'item-m4558-' . strtolower($sku),
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

        $joinpricing = CommercePedagogicalPromotionJoinPricingService::create($DB);
        $joinpricing->configure(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            'EUR',
            100,
            null,
            $now
        );
        if ($withrub) {
            $joinpricing->configure(
                (int)$promotion->get_id(),
                (int)$product->get_id(),
                'RUB',
                15000,
                null,
                $now
            );
        }

        return compact(
            'user',
            'course',
            'product',
            'promotion',
            'eurprice',
            'rubprice'
        );
    }

    private function join_grant(
        array $scenario,
        string $purchase
    ): CommerceEntitlementGrant {
        $promotionid = (int)$scenario['promotion']->get_id();
        $courseid = (int)$scenario['course']->id;
        $productid = (int)$scenario['product']->get_id();
        $userid = (int)$scenario['user']->id;
        $sku = $scenario['product']->get_sku();

        return new CommerceEntitlementGrant(
            'ent-' . sha1($purchase),
            $purchase,
            $sku,
            $sku,
            CommercePedagogicalPromotionJoinGrant::GRANT_TYPE,
            CommercePedagogicalPromotionJoinGrant::resource_key(
                $promotionid,
                $courseid,
                $productid
            ),
            1,
            $userid,
            (string)$scenario['user']->email,
            time(),
            null,
            [
                'commerceoperation' => 'promotion_join',
                'promotion_join_user_id' => $userid,
                'promotion_join_promotion_id' => $promotionid,
                'promotion_join_course_id' => $courseid,
                'promotion_join_product_id' => $productid,
                'promotion_join_product_sku' => $sku,
                'promotion_join_ownership_source' => 'native_entitlement',
            ]
        );
    }

    public function test_m455_expired_paid_hold_reacquires_the_same_pinned_promotion_when_free(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->setup_owner_offer('M455-PIN', $now, 1);
        $cartuuid = str_repeat('5', 32);
        $purchase = 'cmp_m455_pin';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M455-PIN',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now,
            5
        );

        $purchaserecord = (object)[
            'reference' => $purchase,
            'metadatajson' => json_encode(['cart_uuid' => $cartuuid]),
        ];
        CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
            ->prepare_paid_purchase(
                $purchaserecord,
                [$this->join_grant($s, $purchase)],
                $now + 10
            );

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find(
                (int)$s['promotion']->get_id(),
                (int)$s['product']->get_id(),
                $cartuuid
            );
        self::assertNotNull($reservation);
        self::assertSame(
            CommercePedagogicalSeatReservation::ACTIVE,
            $reservation->get_state()
        );
        self::assertSame((int)$s['promotion']->get_id(), $reservation->get_promotion_id());
        self::assertTrue($reservation->is_active_at($now + 10));
    }

    public function test_m456_expired_paid_hold_never_spills_into_a_newer_cohort(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->setup_owner_offer(
            'M456-NOSPILL',
            $now,
            1,
            false,
            $now - 100,
            $now + 5
        );
        $cartuuid = str_repeat('6', 32);
        $purchase = 'cmp_m456_nospill';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M456-NOSPILL',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now,
            5
        );

        // Same stable product, newer cohort becomes the public sale after the
        // original payment hold expires.
        $promotion2 = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm456-nospill-p2',
                'M456 P2',
                (int)$s['course']->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now + 6,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                1,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion2->get_id(),
            (int)$s['product']->get_id(),
            1,
            null,
            $now
        );

        $purchaserecord = (object)[
            'reference' => $purchase,
            'metadatajson' => json_encode(['cart_uuid' => $cartuuid]),
        ];

        try {
            CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
                ->prepare_paid_purchase(
                    $purchaserecord,
                    [$this->join_grant($s, $purchase)],
                    $now + 10
                );
            self::fail('A paid retry must not jump from the pinned cohort to a newer cohort.');
        } catch (CommercePedagogicalSeatReservationException $exception) {
            self::assertSame(
                CommercePedagogicalCapacityService::SALES_CLOSED,
                $exception->get_code_key()
            );
        }

        $old = CommercePedagogicalSeatReservationRepository::create($DB)->find(
            (int)$s['promotion']->get_id(),
            (int)$s['product']->get_id(),
            $cartuuid
        );
        self::assertNotNull($old);
        self::assertSame(CommercePedagogicalSeatReservation::EXPIRED, $old->get_state());

        $new = CommercePedagogicalSeatReservationRepository::create($DB)->find(
            (int)$promotion2->get_id(),
            (int)$s['product']->get_id(),
            $cartuuid
        );
        self::assertNull($new);
    }

    public function test_m456_expired_paid_hold_fails_closed_when_the_pinned_seat_was_taken(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->setup_owner_offer('M456-FULL', $now, 1);
        $other = $this->getDataGenerator()->create_user();
        $cartuuid = str_repeat('7', 32);
        $purchase = 'cmp_m456_full';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M456-FULL',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now,
            5
        );
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$s['promotion']->get_id(),
            (int)$s['course']->id,
            (int)$other->id,
            'M456-FULL',
            'cmp_m456_other',
            $now + 6
        );

        $purchaserecord = (object)[
            'reference' => $purchase,
            'metadatajson' => json_encode(['cart_uuid' => $cartuuid]),
        ];

        $this->expectException(CommercePedagogicalSeatReservationException::class);
        CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
            ->prepare_paid_purchase(
                $purchaserecord,
                [$this->join_grant($s, $purchase)],
                $now + 10
            );
    }

    public function test_m457_payment_method_retry_creates_a_new_attempt_without_mutating_purchase_or_extending_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->setup_owner_offer('M457-METHOD', $now, 1);
        $cartuuid = str_repeat('8', 32);

        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $reservations->reserve(
            'M457-METHOD',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now,
            120
        );
        $firstlease = $reservations->renew_lease(
            'M457-METHOD',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now + 10,
            CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            'payment'
        );
        self::assertNotNull($firstlease);
        self::assertSame($now + 10, $firstlease->get_payment_started_at());
        self::assertSame(
            $now + 10 + CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            $firstlease->get_expires_at()
        );

        // A second launch with another method must not reset the payment
        // anchor or grant another ten minutes.
        $secondlease = $reservations->renew_lease(
            'M457-METHOD',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now + 300,
            CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            'payment'
        );
        self::assertNotNull($secondlease);
        self::assertSame($firstlease->get_payment_started_at(), $secondlease->get_payment_started_at());
        self::assertSame($firstlease->get_expires_at(), $secondlease->get_expires_at());

        $reference = 'cmp_' . bin2hex(random_bytes(12));
        $item = new CommercePurchaseRequestItem(
            new CommerceItem(
                CommerceItem::TYPE_SUBSCRIPTION,
                'M457-METHOD',
                'M457 accompaniment',
                null,
                ['operation' => 'promotion_join']
            ),
            1,
            100,
            'EUR',
            ['operation' => 'promotion_join']
        );
        $customer = new CommerceCustomer(
            (int)$s['user']->id,
            (string)$s['user']->email
        );
        $payments = new CommercePaymentRepository($DB);
        $persister = new CommerceCheckoutPurchasePersister(
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommercePurchasePersistenceMapper(),
            $payments
        );

        $stripe = new CommercePurchaseRequest(
            $reference,
            $customer,
            [$item],
            CommercePurchaseRequestStatus::PAYMENT_PENDING,
            'stripe',
            '/success',
            '/cancel',
            ['payment_method' => 'card', 'operation' => 'promotion_join'],
            $now
        );
        $first = $persister->persist_with_result($stripe);
        self::assertNotNull($first->get_payment_attempt());

        $paypal = new CommercePurchaseRequest(
            $reference,
            $customer,
            [$item],
            CommercePurchaseRequestStatus::PAYMENT_PENDING,
            'paypal',
            '/success',
            '/cancel',
            ['payment_method' => 'paypal', 'operation' => 'promotion_join'],
            $now + 300
        );
        $second = $persister->persist_with_result($paypal);
        self::assertNotNull($second->get_payment_attempt());
        self::assertSame(
            $first->get_payment_attempt()?->get_purchase_uuid(),
            $second->get_payment_attempt()?->get_purchase_uuid()
        );

        $attempts = $payments->find_for_purchase(
            (string)$first->get_payment_attempt()?->get_purchase_uuid()
        );
        self::assertCount(2, $attempts);
        self::assertSame('paypal', $attempts[0]->get_provider());
        self::assertSame('paypal', $attempts[0]->get_metadata()['payment_method']);
        self::assertSame('stripe', $attempts[1]->get_provider());
        self::assertSame('card', $attempts[1]->get_metadata()['payment_method']);
        self::assertSame(1, $DB->count_records(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $reference]
        ));
    }

    public function test_m458_direct_currency_switch_reprices_owner_join_and_preserves_pinned_cohort(): void {
        global $DB, $SESSION;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->setup_owner_offer('M458-FX', $now, 2, true);
        $userid = (int)$s['user']->id;
        $SESSION->local_subscriptions_commerce_carts = [];

        $carts = CommerceCartRuntimeFactory::create();
        $prepared = $carts->prepare_direct_product(
            $userid,
            'EUR',
            'fr',
            'M458-FX',
            (int)$s['eurprice']->get_id(),
            1,
            ['operation' => 'promotion_join'],
            $now
        );
        self::assertTrue($prepared->has_changed());

        $sourcecart = $prepared->get_cart();
        $sourceitems = $sourcecart->get_items();
        self::assertCount(1, $sourceitems);
        $sourceitem = reset($sourceitems);
        self::assertNotFalse($sourceitem);
        self::assertSame(100, $sourceitem->get_metadata()['promotion_join_amount_minor']);
        self::assertSame('EUR', $sourceitem->get_metadata()['promotion_join_currency']);

        // transfer_cart() rebinds the existing reservation row when no target
        // row already exists. Capture its identity/deadline before the switch:
        // after the switch the old cart UUID must no longer own a row, while
        // the same reservation id must be active under the new direct-cart UUID.
        $reservationrepo = CommercePedagogicalSeatReservationRepository::create($DB);
        $originalhold = $reservationrepo->find(
            (int)$s['promotion']->get_id(),
            (int)$s['product']->get_id(),
            $sourcecart->get_uuid()
        );
        self::assertNotNull($originalhold);
        $originalholdid = $originalhold->get_id();
        $originalexpiresat = $originalhold->get_expires_at();

        $switched = CommerceDirectPurchaseCurrencySwitchService::create()->switch(
            $userid,
            [
                'currency' => 'EUR',
                'sku' => 'M458-FX',
                'priceid' => (int)$s['eurprice']->get_id(),
                'quantity' => 1,
                'metadata' => $sourceitem->get_metadata(),
                'cartuuid' => $sourcecart->get_uuid(),
            ],
            'RUB',
            'fr',
            $now + 1
        );
        self::assertTrue($switched->has_changed());

        $targetcart = $switched->get_cart();
        self::assertSame('RUB', $targetcart->get_currency());
        $targetitems = $targetcart->get_items();
        self::assertCount(1, $targetitems);
        $targetitem = reset($targetitems);
        self::assertNotFalse($targetitem);
        $metadata = $targetitem->get_metadata();
        self::assertSame('promotion_join', $metadata['operation']);
        self::assertSame((int)$s['promotion']->get_id(), $metadata['promotion_join_promotion_id']);
        self::assertSame('RUB', $metadata['promotion_join_currency']);
        self::assertSame(15000, $metadata['promotion_join_amount_minor']);
        self::assertNotSame(
            $sourceitem->get_metadata()['promotion_join_price_id'],
            $metadata['promotion_join_price_id']
        );

        $snapshot = $carts->direct_snapshot(
            $userid,
            'RUB',
            'fr',
            'M458-FX',
            (int)$s['rubprice']->get_id(),
            1,
            $metadata,
            $now + 1,
            $targetcart->get_uuid(),
            null,
            false
        );
        self::assertSame(15000, $snapshot->get_totals()->get_total()->get_amount_minor());

        $sourcehold = $reservationrepo->find(
            (int)$s['promotion']->get_id(),
            (int)$s['product']->get_id(),
            $sourcecart->get_uuid()
        );
        $targethold = $reservationrepo->find(
            (int)$s['promotion']->get_id(),
            (int)$s['product']->get_id(),
            $targetcart->get_uuid()
        );
        self::assertNull($sourcehold);
        self::assertNotNull($targethold);
        self::assertSame($originalholdid, $targethold->get_id());
        self::assertSame($originalexpiresat, $targethold->get_expires_at());
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $targethold->get_state());
        self::assertSame((int)$s['promotion']->get_id(), $targethold->get_promotion_id());
    }
}
