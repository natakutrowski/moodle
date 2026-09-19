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
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationPurchaseLifecycle;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\returnflow\CommercePaymentEventSynchronizer;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

/**
 * M4.5 payment failure/retry contract for Native Commerce and promotion_join.
 */
final class commerce_797m45_payment_retry_lifecycle_test extends advanced_testcase {
    private const PURCHASE_UUID = '45111111222243338444555555555555';
    private const PURCHASE_REFERENCE = 'cmp_m45_payment_retry';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->insert_purchase();
    }

    public function test_paid_attempt_cannot_be_downgraded_by_late_payment_failed(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(
            self::PURCHASE_UUID,
            'stripe',
            100,
            'EUR'
        );
        $payments->update_status(
            (int)$attempt->get_id(),
            CommercePaymentAttemptStatus::PAID,
            'pi_m45_paid',
            ['provider_status' => 'paid'],
            time()
        );

        $event = new InternalEvent('payment_failed', [
            'meta' => [
                'provider' => 'stripe',
                'commerce_payment_id' => (string)$attempt->get_id(),
                'commerce_purchase_uuid' => self::PURCHASE_UUID,
                'payment_intent' => 'pi_m45_paid',
            ],
        ]);

        self::assertTrue(
            (new CommercePaymentEventSynchronizer($payments))->synchronize($event)
        );
        self::assertSame(
            CommercePaymentAttemptStatus::PAID,
            $payments->find((int)$attempt->get_id())?->get_status()
        );
    }

    public function test_paid_attempt_cannot_be_downgraded_by_late_checkout_expired(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(
            self::PURCHASE_UUID,
            'stripe',
            100,
            'EUR'
        );
        $payments->update_status(
            (int)$attempt->get_id(),
            CommercePaymentAttemptStatus::PAID,
            'pi_m45_expired_after_paid',
            null,
            time()
        );

        $event = new InternalEvent('checkout_expired', [
            'meta' => [
                'provider' => 'stripe',
                'commerce_payment_id' => (string)$attempt->get_id(),
                'commerce_purchase_uuid' => self::PURCHASE_UUID,
            ],
        ]);

        self::assertTrue(
            (new CommercePaymentEventSynchronizer($payments))->synchronize($event)
        );
        self::assertSame(
            CommercePaymentAttemptStatus::PAID,
            $payments->find((int)$attempt->get_id())?->get_status()
        );
    }

    public function test_failed_attempt_can_later_become_paid(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(
            self::PURCHASE_UUID,
            'stripe',
            100,
            'EUR'
        );
        $payments->update_status(
            (int)$attempt->get_id(),
            CommercePaymentAttemptStatus::FAILED
        );

        $event = new InternalEvent('checkout_completed', [
            'meta' => [
                'provider' => 'stripe',
                'commerce_payment_id' => (string)$attempt->get_id(),
                'commerce_purchase_uuid' => self::PURCHASE_UUID,
                'payment_intent' => 'pi_m45_recovered',
            ],
        ]);

        self::assertTrue(
            (new CommercePaymentEventSynchronizer($payments))->synchronize($event)
        );
        self::assertSame(
            CommercePaymentAttemptStatus::PAID,
            $payments->find((int)$attempt->get_id())?->get_status()
        );
    }

    public function test_cancelled_attempt_can_later_become_paid(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(
            self::PURCHASE_UUID,
            'stripe',
            100,
            'EUR'
        );
        $payments->update_status(
            (int)$attempt->get_id(),
            CommercePaymentAttemptStatus::CANCELLED
        );

        $event = new InternalEvent('checkout_completed', [
            'meta' => [
                'provider' => 'stripe',
                'commerce_payment_id' => (string)$attempt->get_id(),
                'commerce_purchase_uuid' => self::PURCHASE_UUID,
                'payment_intent' => 'pi_m45_cancel_recovered',
            ],
        ]);

        self::assertTrue(
            (new CommercePaymentEventSynchronizer($payments))->synchronize($event)
        );
        self::assertSame(
            CommercePaymentAttemptStatus::PAID,
            $payments->find((int)$attempt->get_id())?->get_status()
        );
    }

    public function test_router_only_applies_failure_side_effects_when_native_failure_won(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/payment/EventRouter.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('native_attempt_has_status', $source);
        self::assertStringContainsString('CommercePaymentAttemptStatus::FAILED', $source);
        self::assertStringContainsString('CommercePaymentAttemptStatus::CANCELLED', $source);
        self::assertStringContainsString("['payment_failed', 'checkout_expired']", $source);
        self::assertStringContainsString("if (\$event->type === 'checkout_expired')", $source);
        self::assertStringContainsString('release_for_payment_event', $source);
    }

    public function test_browser_cancel_does_not_release_pedagogical_hold(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/return.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("if (\$result === 'cancel')", $source);
        self::assertStringContainsString(
            'CommercePaymentAttemptStatus::CANCELLED',
            $source
        );
        self::assertStringNotContainsString(
            'release_for_payment_event',
            $source
        );
    }


    public function test_expired_hold_is_not_reacquired_when_capacity_was_taken(): void {
        global $DB;

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm45-expired-full',
                'M4.5 expired/full',
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
        ))->save(new CommerceProduct(
            'M45-FULL',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'M45-FULL'
        ));
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            1,
            null,
            $now
        );

        $cartuuid = str_repeat('d', 32);
        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M45-FULL',
            $cartuuid,
            (int)$owner->id,
            1,
            $now,
            5
        );

        // Another paid participant takes the only real place while the first
        // customer's payment is still pending and its temporary hold expires.
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$other->id,
            'M45-FULL',
            'cmp_m45_other',
            $now + 6
        );

        $purchase = (object)[
            'reference' => 'cmp_m45_owner',
            'metadatajson' => json_encode(['cart_uuid' => $cartuuid]),
        ];
        $grant = new CommerceEntitlementGrant(
            'ent-m45-owner',
            'cmp_m45_owner',
            'M45-FULL',
            'M45-FULL',
            'pedagogical_promotion_join',
            'promotion:' . (int)$promotion->get_id()
                . ':course:' . (int)$course->id
                . ':product:' . (int)$product->get_id(),
            1,
            (int)$owner->id,
            'owner@example.test',
            $now,
            null
        );

        try {
            CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
                ->prepare_paid_purchase($purchase, [$grant], $now + 10);
            self::fail('Expired hold must not be reacquired when the promotion is now full.');
        } catch (CommercePedagogicalSeatReservationException $exception) {
            self::assertNotSame('', $exception->getMessage());
        }

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find(
                (int)$promotion->get_id(),
                (int)$product->get_id(),
                $cartuuid
            );
        self::assertNotNull($reservation);
        self::assertSame(
            CommercePedagogicalSeatReservation::EXPIRED,
            $reservation->get_state()
        );
    }

    private function insert_purchase(): void {
        global $DB;

        $now = time();
        $DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object)[
                'purchaseuuid' => self::PURCHASE_UUID,
                'reference' => self::PURCHASE_REFERENCE,
                'type' => 'checkout',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'm45@example.test',
                'status' => 'payment_pending',
                'currency' => 'EUR',
                'subtotalminor' => 100,
                'discountminor' => 0,
                'totalminor' => 100,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }
}
