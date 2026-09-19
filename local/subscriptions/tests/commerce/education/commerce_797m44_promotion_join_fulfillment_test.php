<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinGrant;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationPurchaseLifecycle;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePurchaseGrantPlanner;
use local_subscriptions\commerce\fulfillment\native\education\CommercePedagogicalPromotionJoinFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/** M4.4 dedicated fulfillment contract for an existing owner joining a promotion. */
final class commerce_797m44_promotion_join_fulfillment_test extends advanced_testcase {
    /** @return array<string,mixed> */
    private function scenario(string $sku, int $now): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id, 'student');

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            $now
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
                $now,
                null,
                3,
                null,
                null,
                $now,
                $now
            )
        );

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $product = $products->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $sku
        ));
        $price = (new CommerceProductPriceRepository($DB, $hydrator, $products))->save(
            new CommerceProductPrice($sku, CommerceMoney::from_minor(200, 'EUR'), true)
        );

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            3,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            3,
            null,
            null,
            $now,
            $now
        ));
        $group = CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            'Les Cigales',
            0,
            null,
            'fr',
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        return compact('course', 'user', 'promotion', 'product', 'price', 'group');
    }

    /** @param array<string,mixed> $s */
    private function join_grant(array $s, string $sku, string $purchase, int $now): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-' . substr(hash('sha256', $purchase), 0, 24),
            $purchase,
            'item-' . substr(hash('sha256', $sku), 0, 24),
            $sku,
            CommercePedagogicalPromotionJoinGrant::GRANT_TYPE,
            CommercePedagogicalPromotionJoinGrant::resource_key(
                (int)$s['promotion']->get_id(),
                (int)$s['course']->id,
                (int)$s['product']->get_id()
            ),
            1,
            (int)$s['user']->id,
            (string)$s['user']->email,
            $now,
            null,
            [
                'commerceoperation' => CommercePedagogicalPromotionJoinOperation::OPERATION,
                'promotion_join_user_id' => (int)$s['user']->id,
                'promotion_join_promotion_id' => (int)$s['promotion']->get_id(),
                'promotion_join_course_id' => (int)$s['course']->id,
                'promotion_join_product_id' => (int)$s['product']->get_id(),
                'promotion_join_product_sku' => $sku,
                'promotion_join_ownership_source' => 'native_entitlement',
            ]
        );
    }

    public function test_planner_replaces_course_access_with_dedicated_promotion_join_grant(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M44-PLAN', $now);

        $purchase = (object)[
            'reference' => 'cmp_m44_plan',
            'userid' => (int)$s['user']->id,
            'customeremail' => (string)$s['user']->email,
        ];
        $metadata = [
            'operation' => 'promotion_join',
            'promotion_join_user_id' => (int)$s['user']->id,
            'promotion_join_promotion_id' => (int)$s['promotion']->get_id(),
            'promotion_join_course_id' => (int)$s['course']->id,
            'promotion_join_product_id' => (int)$s['product']->get_id(),
            'promotion_join_product_sku' => 'M44-PLAN',
            'promotion_join_ownership_source' => 'native_entitlement',
            'promotion_join_price_id' => 41,
            'promotion_join_amount_minor' => 100,
            'promotion_join_currency' => 'EUR',
            'priceid' => (int)$s['price']->get_id(),
        ];
        $item = (object)[
            'id' => 901,
            'itemreference' => 'M44-PLAN',
            'quantity' => 1,
            'metadatajson' => json_encode($metadata),
            'fulfillmentjson' => '{}',
        ];

        $plan = (new CommerceNativePurchaseGrantPlanner($DB))->plan($purchase, [$item], $now);
        $grants = $plan->get_grants();

        self::assertCount(1, $grants);
        self::assertSame(CommercePedagogicalPromotionJoinGrant::GRANT_TYPE, $grants[0]->get_type());
        self::assertNotSame('course_access', $grants[0]->get_type());
        self::assertSame(
            'promotion:' . $s['promotion']->get_id() . ':course:' . $s['course']->id . ':product:' . $s['product']->get_id(),
            $grants[0]->get_resource_key()
        );
        self::assertSame('promotion_join', $grants[0]->get_configuration()['commerceoperation']);
    }

    public function test_handler_validates_join_without_mutating_course_enrolment(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M44-HANDLER', $now);
        $grant = $this->join_grant($s, 'M44-HANDLER', 'cmp_m44_handler', $now);
        $before = $this->course_enrolment_count((int)$s['user']->id, (int)$s['course']->id);

        $result = (new CommercePedagogicalPromotionJoinFulfillmentHandler())->fulfill(
            $grant,
            CommerceNativeFulfillmentContext::runtime('m44-handler', $now)
        );

        self::assertTrue($result->is_completed());
        self::assertFalse($result->get_payload()['courseaccessmutation']);
        self::assertSame($before, $this->course_enrolment_count((int)$s['user']->id, (int)$s['course']->id));
        self::assertNull(
            CommerceStudentCourseAccessRepository::create($DB)->find(
                (int)$s['course']->id,
                (int)$s['user']->id
            )
        );
    }

    public function test_paid_join_creates_lifetime_participation_group_and_consumes_hold_without_second_enrolment(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M44-APPLY', $now);
        $cartuuid = str_repeat('d', 32);
        $purchase = 'cmp_m44_apply';
        $grant = $this->join_grant($s, 'M44-APPLY', $purchase, $now);

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M44-APPLY',
            $cartuuid,
            (int)$s['user']->id,
            1,
            $now
        );
        $before = $this->course_enrolment_count((int)$s['user']->id, (int)$s['course']->id);

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$grant],
            $now + 1,
            $cartuuid
        );

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$s['course']->id,
            (int)$s['user']->id
        );
        self::assertNotNull($access);
        self::assertSame(CommerceStudentAccessProfile::LIFETIME_FULL, $access->get_profile());
        self::assertSame((int)$s['promotion']->get_id(), $access->get_promotion_id());

        $participations = CommercePedagogicalParticipationRepository::create($DB)
            ->active_for_purchase($purchase);
        self::assertCount(1, $participations);
        self::assertSame((int)$s['product']->get_id(), (int)$participations[0]->productid);

        self::assertTrue(groups_is_member($s['group']->get_moodle_group_id(), (int)$s['user']->id));
        self::assertSame(
            $s['group']->get_id(),
            CommercePedagogicalGroupRepository::create($DB)
                ->group_for_user((int)$s['promotion']->get_id(), (int)$s['user']->id)?->get_id()
        );

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find((int)$s['promotion']->get_id(), (int)$s['product']->get_id(), $cartuuid);
        self::assertSame(CommercePedagogicalSeatReservation::CONSUMED, $reservation?->get_state());
        self::assertSame($purchase, $reservation?->get_purchase_reference());
        self::assertSame($before, $this->course_enrolment_count((int)$s['user']->id, (int)$s['course']->id));

        // A paid webhook/reconciliation replay must not reactivate a hold that
        // was already consumed by this exact purchase.
        $purchasecontext = (object)[
            'reference' => $purchase,
            'metadatajson' => json_encode(['cart_uuid' => $cartuuid]),
        ];
        self::assertSame(
            $cartuuid,
            CommercePedagogicalSeatReservationPurchaseLifecycle::create($DB)
                ->prepare_paid_purchase($purchasecontext, [$grant], $now + 2)
        );
        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find((int)$s['promotion']->get_id(), (int)$s['product']->get_id(), $cartuuid);
        self::assertSame(CommercePedagogicalSeatReservation::CONSUMED, $reservation?->get_state());
        self::assertSame($purchase, $reservation?->get_purchase_reference());

        // Webhook/reconciliation replay remains idempotent at the pedagogy layer.
        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$grant],
            $now + 2,
            $cartuuid
        );
        self::assertCount(
            1,
            CommercePedagogicalParticipationRepository::create($DB)->active_for_purchase($purchase)
        );
        self::assertSame($before, $this->course_enrolment_count((int)$s['user']->id, (int)$s['course']->id));
    }

    public function test_promotion_join_grant_does_not_become_course_product_ownership(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M44-OWNERSHIP', $now);
        $grant = $this->join_grant($s, 'M44-OWNERSHIP', 'cmp_m44_ownership', $now);

        $record = (new CommerceEntitlementGrantRecordMapper())->to_record(
            $grant,
            'active',
            $now
        );
        $DB->insert_record('local_subs_commerce_grant', $record);

        $ownership = new CommerceStorefrontOwnershipResolver($DB);
        self::assertSame(
            'none',
            $ownership->resolve_source((int)$s['user']->id, 'M44-OWNERSHIP')
        );
    }

    public function test_runtime_payment_gate_is_removed_only_with_dedicated_handler_wired(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $runtime = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutRuntime.php'
        );
        $completer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/fulfillment/native/checkout/CommerceNativePaidPurchaseCompleter.php'
        );

        self::assertIsString($runtime);
        self::assertIsString($completer);
        self::assertStringNotContainsString('commerce_promotion_join_payment_pending_fulfillment', $runtime);
        self::assertStringContainsString('CommercePedagogicalPromotionJoinFulfillmentHandler', $completer);
    }

    private function course_enrolment_count(int $userid, int $courseid): int {
        global $DB;

        return (int)$DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :userid
                AND e.courseid = :courseid",
            ['userid' => $userid, 'courseid' => $courseid]
        );
    }
}
