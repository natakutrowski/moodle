<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationPurchaseLifecycle;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

final class commerce_797k7_reservation_purchase_lifecycle_test extends advanced_testcase {
    /**
     * @return array{
     *   promotion:CommercePedagogicalPromotion,
     *   product:CommerceProduct,
     *   courseid:int,
     *   userid:int
     * }
     */
    private function setup_offer(string $sku, int $now): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        CommerceCourseAccessConfigurationRepository::create($DB)
            ->set_mode(
                (int)$course->id,
                CommerceCourseAccessMode::PROMOTION,
                $now
            );

        $promotion =
            CommercePedagogicalPromotionRepository::create($DB)->save(
                new CommercePedagogicalPromotion(
                    null,
                    strtolower($sku),
                    $sku,
                    (int)$course->id,
                    CommercePedagogicalPromotionStatus::OPEN,
                    true,
                    null,
                    null,
                    $now,
                    null,
                    1,
                    null,
                    null,
                    $now,
                    $now
                )
            );

        $product = (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(
            new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            )
        );

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            1,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                1,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            $sku . ' group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        return [
            'promotion' => $promotion,
            'product' => $product,
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
        ];
    }

    private function grant(
        array $setup,
        string $sku,
        string $purchase
    ): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-' . sha1($purchase),
            $purchase,
            'item-' . sha1($sku),
            $sku,
            CommerceCourseAccessFulfillmentHandler::GRANT_TYPE,
            'course:' . (string)$setup['courseid'] . ':full',
            1,
            $setup['userid'],
            'student@example.test',
            time(),
            null
        );
    }

    public function test_paid_purchase_consumes_hold_only_after_participation_exists(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_offer('K7-PAID', $now);
        $cartuuid = str_repeat('a', 32);
        $purchase = 'CFR-K7-PAID';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K7-PAID',
            $cartuuid,
            $setup['userid'],
            1,
            $now
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$this->grant($setup, 'K7-PAID', $purchase)],
            $now + 1,
            $cartuuid
        );

        $participations =
            CommercePedagogicalParticipationRepository::create($DB)
                ->active_for_purchase($purchase);
        self::assertCount(1, $participations);

        $reservation =
            CommercePedagogicalSeatReservationRepository::create($DB)
                ->find(
                    (int)$setup['promotion']->get_id(),
                    (int)$setup['product']->get_id(),
                    $cartuuid
                );

        self::assertSame(
            CommercePedagogicalSeatReservation::CONSUMED,
            $reservation?->get_state()
        );
        self::assertSame(
            $purchase,
            $reservation?->get_purchase_reference()
        );
    }

    public function test_prepare_paid_purchase_reacquires_expired_hold_when_seat_is_free(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_offer('K7-REACQUIRE', $now);
        $cartuuid = str_repeat('b', 32);
        $purchase = 'CFR-K7-REACQUIRE';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K7-REACQUIRE',
            $cartuuid,
            $setup['userid'],
            1,
            $now,
            5
        );

        $purchaserecord = (object)[
            'metadatajson' => json_encode([
                'cart_uuid' => $cartuuid,
            ]),
        ];

        $resolved =
            CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
                ->prepare_paid_purchase(
                    $purchaserecord,
                    [$this->grant(
                        $setup,
                        'K7-REACQUIRE',
                        $purchase
                    )],
                    $now + 10
                );

        self::assertSame($cartuuid, $resolved);

        $reservation =
            CommercePedagogicalSeatReservationRepository::create($DB)
                ->find(
                    (int)$setup['promotion']->get_id(),
                    (int)$setup['product']->get_id(),
                    $cartuuid
                );

        self::assertTrue(
            $reservation?->is_active_at($now + 10)
        );
    }

    public function test_failed_payment_event_releases_entire_cart_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_offer('K7-FAILED', $now);
        $cartuuid = str_repeat('c', 32);
        $purchaseuuid =
            '11111111222243338444555555555555';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K7-FAILED',
            $cartuuid,
            $setup['userid'],
            1,
            $now
        );

        $DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object)[
                'purchaseuuid' => $purchaseuuid,
                'reference' => 'CFR-K7-FAILED',
                'type' => 'checkout',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => $setup['userid'],
                'customeremail' => 'student@example.test',
                'status' => 'payment_pending',
                'currency' => 'EUR',
                'subtotalminor' => 100,
                'discountminor' => 0,
                'totalminor' => 100,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => json_encode([
                    'cart_uuid' => $cartuuid,
                ]),
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $event = new InternalEvent(
            'payment_failed',
            [
                'meta' => [
                    'commerce_purchase_uuid' => $purchaseuuid,
                ],
            ]
        );

        $released =
            CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
                ->release_for_payment_event(
                    $event,
                    $now + 1
                );

        self::assertSame(1, $released);

        $reservation =
            CommercePedagogicalSeatReservationRepository::create($DB)
                ->find(
                    (int)$setup['promotion']->get_id(),
                    (int)$setup['product']->get_id(),
                    $cartuuid
                );

        self::assertSame(
            CommercePedagogicalSeatReservation::RELEASED,
            $reservation?->get_state()
        );
    }

    public function test_event_router_wires_failure_release_before_guest_failure_handling(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/payment/EventRouter.php'
        );

        $releasepos = strpos(
            $source,
            'release_for_payment_event'
        );
        $guestpos = strpos(
            $source,
            'self::update_guest_checkout_failure'
        );

        self::assertNotFalse($releasepos);
        self::assertNotFalse($guestpos);
        self::assertLessThan($guestpos, $releasepos);
    }

    public function test_paid_completer_wires_prepare_then_pedagogical_apply(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root .
            '/classes/commerce/fulfillment/native/checkout/' .
            'CommerceNativePaidPurchaseCompleter.php'
        );

        $preparepos = strpos(
            $source,
            'prepare_paid_purchase'
        );
        $applypos = strpos(
            $source,
            'CommercePedagogicalPurchaseOrchestrator::create'
        );

        self::assertNotFalse($preparepos);
        self::assertNotFalse($applypos);
        self::assertLessThan($applypos, $preparepos);
    }
}
