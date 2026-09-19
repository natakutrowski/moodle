<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
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
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibility;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinGrant;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/** M4.7 anti-double and same-promotion rejoin contract. */
final class commerce_797m47_promotion_join_antidouble_rejoin_test extends advanced_testcase {
    /** @return array<string,mixed> */
    private function scenario(string $sku, int $now, bool $groups = false): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id, 'student');

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            $now
        );

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
                4,
                null,
                null,
                $now,
                $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            4,
            null,
            $now
        );

        if ($groups) {
            $repository = CommercePedagogicalGroupRepository::create($DB);
            $repository->save_configuration(new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                4,
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
        } else {
            $group = null;
        }

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'owner-' . substr(hash('sha256', $sku . $user->id), 0, 24),
            'idempotencykey' => 'owner-idem-' . substr(hash('sha256', $sku . $user->id), 0, 20),
            'purchasereference' => 'owner-purchase-' . $user->id,
            'itemreference' => 'owner-item-' . $user->id,
            'productsku' => $sku,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => (int)$user->id,
            'beneficiaryemail' => (string)$user->email,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return compact('course', 'user', 'product', 'promotion', 'group');
    }

    /** @param array<string,mixed> $s */
    private function join_grant(
        array $s,
        string $sku,
        string $purchase,
        int $now
    ): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'join-' . substr(hash('sha256', $purchase), 0, 24),
            $purchase,
            'item-' . substr(hash('sha256', $purchase . $sku), 0, 24),
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

    public function test_cancelled_and_refunded_history_allow_same_promotion_rejoin_but_active_does_not(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M47-REJOIN', $now);
        $participations = CommercePedagogicalParticipationRepository::create($DB);
        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB);

        $participations->record_active(
            (int)$s['promotion']->get_id(),
            (int)$s['course']->id,
            (int)$s['user']->id,
            'M47-REJOIN',
            'cmp-m47-old-cancelled',
            $now
        );
        $participations->change_state_for_purchase(
            'cmp-m47-old-cancelled',
            CommercePedagogicalParticipationRepository::CANCELLED,
            $now + 1
        );

        $decision = $eligibility->resolve((int)$s['user']->id, 'M47-REJOIN', $now + 2);
        self::assertTrue($decision->is_eligible());

        $participations->record_active(
            (int)$s['promotion']->get_id(),
            (int)$s['course']->id,
            (int)$s['user']->id,
            'M47-REJOIN',
            'cmp-m47-old-refunded',
            $now + 3
        );
        $participations->change_state_for_purchase(
            'cmp-m47-old-refunded',
            CommercePedagogicalParticipationRepository::REFUNDED,
            $now + 4
        );

        $decision = $eligibility->resolve((int)$s['user']->id, 'M47-REJOIN', $now + 5);
        self::assertTrue($decision->is_eligible());

        $participations->record_active(
            (int)$s['promotion']->get_id(),
            (int)$s['course']->id,
            (int)$s['user']->id,
            'M47-REJOIN',
            'cmp-m47-current-active',
            $now + 6
        );

        $decision = $eligibility->resolve((int)$s['user']->id, 'M47-REJOIN', $now + 7);
        self::assertFalse($decision->is_eligible());
        self::assertSame(
            CommercePedagogicalPromotionJoinEligibility::ALREADY_JOINED,
            $decision->get_reason()
        );
    }

    public function test_live_hold_blocks_another_cart_for_same_owner_but_not_its_own_checkout(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M47-HOLD', $now);
        $cartone = str_repeat('a', 32);
        $carttwo = str_repeat('b', 32);

        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $reservations->reserve(
            'M47-HOLD',
            $cartone,
            (int)$s['user']->id,
            1,
            $now
        );

        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB);
        $owncart = $eligibility->resolve(
            (int)$s['user']->id,
            'M47-HOLD',
            $now + 1,
            $cartone
        );
        self::assertTrue($owncart->is_eligible());

        $othercart = $eligibility->resolve(
            (int)$s['user']->id,
            'M47-HOLD',
            $now + 1,
            $carttwo
        );
        self::assertFalse($othercart->is_eligible());
        self::assertSame(
            CommercePedagogicalPromotionJoinEligibility::JOIN_IN_PROGRESS,
            $othercart->get_reason()
        );

        try {
            $reservations->reserve(
                'M47-HOLD',
                $carttwo,
                (int)$s['user']->id,
                1,
                $now + 1
            );
            self::fail('A second live cart must not reserve the same owner/promotion/offer.');
        } catch (CommercePedagogicalSeatReservationException $exception) {
            self::assertSame('pedagogical_customer_hold_exists', $exception->get_code_key());
        }
    }

    public function test_rejoin_creates_new_active_participation_and_reactivates_group_without_rewriting_history(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/group/lib.php');

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M47-GROUP', $now, true);
        $promotionid = (int)$s['promotion']->get_id();
        $productid = (int)$s['product']->get_id();
        $userid = (int)$s['user']->id;
        $oldpurchase = 'cmp-m47-revoked-old';
        $newpurchase = 'cmp-m47-rejoin-new';
        $participations = CommercePedagogicalParticipationRepository::create($DB);
        $groups = CommercePedagogicalGroupRepository::create($DB);

        $participations->record_active(
            $promotionid,
            (int)$s['course']->id,
            $userid,
            'M47-GROUP',
            $oldpurchase,
            $now
        );
        $assigned = CommercePedagogicalGroupOrchestrator::create($DB)
            ->assign_first_available_for_product(
                $promotionid,
                $productid,
                $userid,
                null,
                $now
            );
        self::assertNotNull($assigned);

        $participations->change_state_for_purchase(
            $oldpurchase,
            CommercePedagogicalParticipationRepository::CANCELLED,
            $now + 1
        );
        $groups->deactivate_membership((int)$assigned->get_id(), $userid, $now + 1);
        groups_remove_member($assigned->get_moodle_group_id(), $userid);

        self::assertNull($groups->group_for_user($promotionid, $userid));
        self::assertFalse(groups_is_member($assigned->get_moodle_group_id(), $userid));

        $cartuuid = str_repeat('c', 32);
        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M47-GROUP',
            $cartuuid,
            $userid,
            1,
            $now + 2
        );
        $grant = $this->join_grant($s, 'M47-GROUP', $newpurchase, $now + 2);

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$grant],
            $now + 3,
            $cartuuid
        );

        $history = array_values($DB->get_records(
            'local_subs_commerce_ped_join',
            ['promotionid' => $promotionid, 'userid' => $userid],
            'id ASC'
        ));
        self::assertCount(2, $history);
        self::assertSame($oldpurchase, (string)$history[0]->purchasereference);
        self::assertSame(CommercePedagogicalParticipationRepository::CANCELLED, (string)$history[0]->state);
        self::assertSame($newpurchase, (string)$history[1]->purchasereference);
        self::assertSame(CommercePedagogicalParticipationRepository::ACTIVE, (string)$history[1]->state);

        $currentgroup = $groups->group_for_user($promotionid, $userid);
        self::assertNotNull($currentgroup);
        self::assertSame((int)$assigned->get_id(), (int)$currentgroup->get_id());
        self::assertTrue(groups_is_member($assigned->get_moodle_group_id(), $userid));

        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)->find(
            $promotionid,
            $productid,
            $cartuuid
        );
        self::assertSame(CommercePedagogicalSeatReservation::CONSUMED, $reservation?->get_state());
        self::assertSame($newpurchase, $reservation?->get_purchase_reference());

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$s['course']->id,
            $userid
        );
        self::assertNotNull($access);
        self::assertSame(CommerceStudentAccessProfile::LIFETIME_FULL, $access->get_profile());
        self::assertSame($promotionid, $access->get_promotion_id());
    }

    public function test_fulfillment_guard_rejects_second_active_purchase_even_with_a_stale_second_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $s = $this->scenario('M47-GUARD', $now);
        $promotionid = (int)$s['promotion']->get_id();
        $productid = (int)$s['product']->get_id();
        $userid = (int)$s['user']->id;
        $cartone = str_repeat('d', 32);
        $carttwo = str_repeat('e', 32);
        $purchaseone = 'cmp-m47-first-paid';
        $purchasetwo = 'cmp-m47-second-paid';

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M47-GUARD',
            $cartone,
            $userid,
            1,
            $now
        );

        // Simulate a stale duplicate hold created before the M4.7 guard was deployed.
        CommercePedagogicalSeatReservationRepository::create($DB)->save(
            new CommercePedagogicalSeatReservation(
                null,
                $promotionid,
                $productid,
                $carttwo,
                $userid,
                1,
                CommercePedagogicalSeatReservation::ACTIVE,
                $now + 15 * MINSECS,
                $now,
                $now,
                null,
                $now,
                $now
            )
        );

        $orchestrator = CommercePedagogicalPurchaseOrchestrator::create($DB);
        $orchestrator->apply(
            [$this->join_grant($s, 'M47-GUARD', $purchaseone, $now)],
            $now + 1,
            $cartone
        );

        try {
            $orchestrator->apply(
                [$this->join_grant($s, 'M47-GUARD', $purchasetwo, $now + 1)],
                $now + 2,
                $carttwo
            );
            self::fail('A second paid purchase must not create another active participation.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('second active purchase', $exception->getMessage());
        }

        $participations = CommercePedagogicalParticipationRepository::create($DB);
        self::assertCount(1, $participations->active_for_purchase($purchaseone));
        self::assertCount(0, $participations->active_for_purchase($purchasetwo));
        self::assertSame(1, $participations->active_count_for_promotion($promotionid));
    }
}
