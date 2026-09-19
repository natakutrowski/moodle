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
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinContext;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibility;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

final class commerce_797m41_promotion_join_eligibility_test extends advanced_testcase {
    private function product(int $courseid, string $sku): CommerceProduct {
        global $DB;

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
                    'course:' . $courseid . ':full'
                ),
            ]);
        return $product;
    }

    private function promotion(int $courseid, string $key, int $now): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                $key,
                'Promotion ' . $key,
                $courseid,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                4,
                null,
                null,
                $now,
                $now
            )
        );
    }

    private function grant_owner(int $userid, string $email, string $sku, int $courseid, int $now): void {
        global $DB;

        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-m41-' . $userid . '-' . strtolower($sku),
            'idempotencykey' => 'idem-m41-' . $userid . '-' . strtolower($sku),
            'purchasereference' => 'purchase-m41-' . $userid . '-' . strtolower($sku),
            'itemreference' => 'item-m41-' . $userid . '-' . strtolower($sku),
            'productsku' => $sku,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => $email,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function test_existing_owner_is_eligible_for_current_open_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->product((int)$course->id, 'M41-OWNER');
        $promotion = $this->promotion((int)$course->id, 'm41-open', $now);

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            3,
            null,
            $now
        );
        $this->grant_owner((int)$user->id, $user->email, 'M41-OWNER', (int)$course->id, $now);

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M41-OWNER', $now);

        self::assertTrue($decision->is_eligible());
        self::assertNull($decision->get_reason());
        self::assertSame('native_entitlement', $decision->get_ownership_source());
        $context = $decision->get_context();
        self::assertNotNull($context);
        self::assertSame((int)$promotion->get_id(), $context->get_promotion_id());
        self::assertSame('m41-open', $context->get_promotion_key());
        self::assertSame((int)$course->id, $context->get_course_id());
        self::assertSame((int)$product->get_id(), $context->get_product_id());
        self::assertSame(3, $context->get_remaining());

        self::assertSame([
            'operation' => 'promotion_join',
            'promotion_join_user_id' => (int)$user->id,
            'promotion_join_promotion_id' => (int)$promotion->get_id(),
            'promotion_join_promotion_key' => 'm41-open',
            'promotion_join_course_id' => (int)$course->id,
            'promotion_join_product_id' => (int)$product->get_id(),
            'promotion_join_product_sku' => 'M41-OWNER',
            'promotion_join_ownership_source' => 'native_entitlement',
        ], CommercePedagogicalPromotionJoinOperation::metadata($context));
    }

    public function test_non_owner_is_denied_even_when_promotion_has_capacity(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->product((int)$course->id, 'M41-NONOWNER');
        $promotion = $this->promotion((int)$course->id, 'm41-nonowner', $now);
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), 3, null, $now
        );

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M41-NONOWNER', $now);

        self::assertFalse($decision->is_eligible());
        self::assertSame(CommercePedagogicalPromotionJoinEligibility::OWNERSHIP_REQUIRED, $decision->get_reason());
        self::assertNull($decision->get_context());
    }

    public function test_active_member_cannot_pay_again_for_same_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->product((int)$course->id, 'M41-ACTIVE');
        $promotion = $this->promotion((int)$course->id, 'm41-active', $now);
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), 3, null, $now
        );
        $this->grant_owner((int)$user->id, $user->email, 'M41-ACTIVE', (int)$course->id, $now);
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$user->id,
            'M41-ACTIVE',
            'purchase-existing-member',
            $now
        );

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M41-ACTIVE', $now);

        self::assertFalse($decision->is_eligible());
        self::assertSame(CommercePedagogicalPromotionJoinEligibility::ALREADY_JOINED, $decision->get_reason());
        self::assertNotNull($decision->get_context());
    }

    public function test_owner_can_join_new_cohort_after_old_cohort_participation(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->product((int)$course->id, 'M41-COHORT');

        $old = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, 'm41-old', 'Old cohort', (int)$course->id,
                CommercePedagogicalPromotionStatus::FINISHED, true,
                $now - 20 * DAYSECS, $now - 10 * DAYSECS,
                $now - 20 * DAYSECS, $now - 5 * DAYSECS,
                3, null, null, $now - 20 * DAYSECS, $now
            )
        );
        $current = $this->promotion((int)$course->id, 'm41-current', $now);
        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$old->get_id(), (int)$product->get_id(), 3, null, $now);
        $offers->link((int)$current->get_id(), (int)$product->get_id(), 3, null, $now);
        $this->grant_owner((int)$user->id, $user->email, 'M41-COHORT', (int)$course->id, $now);
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$old->get_id(), (int)$course->id, (int)$user->id,
            'M41-COHORT', 'purchase-old-cohort', $now - 10 * DAYSECS
        );

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M41-COHORT', $now);

        self::assertTrue($decision->is_eligible());
        self::assertSame((int)$current->get_id(), $decision->get_context()?->get_promotion_id());
    }


    public function test_sales_closed_is_reported_as_business_blocker(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->product((int)$course->id, 'M41-CLOSED');
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, 'm41-closed', 'Closed sale', (int)$course->id,
                CommercePedagogicalPromotionStatus::SCHEDULED, true,
                $now + DAYSECS, $now + 2 * DAYSECS,
                $now + 3 * DAYSECS, null, 3, null, null, $now, $now
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), 3, null, $now
        );
        $this->grant_owner((int)$user->id, $user->email, 'M41-CLOSED', (int)$course->id, $now);

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M41-CLOSED', $now);

        self::assertFalse($decision->is_eligible());
        self::assertSame(CommercePedagogicalCapacityService::SALES_CLOSED, $decision->get_reason());
        self::assertSame((int)$promotion->get_id(), $decision->get_context()?->get_promotion_id());
    }

    public function test_misconfigured_product_cannot_join_promotion_for_another_course(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $ownedcourse = $this->getDataGenerator()->create_course();
        $promotioncourse = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->product((int)$ownedcourse->id, 'M41-MISMATCH');
        $promotion = $this->promotion((int)$promotioncourse->id, 'm41-mismatch', $now);
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), 3, null, $now
        );
        $this->grant_owner((int)$user->id, $user->email, 'M41-MISMATCH', (int)$ownedcourse->id, $now);

        $decision = CommercePedagogicalPromotionJoinEligibilityService::create($DB)
            ->resolve((int)$user->id, 'M41-MISMATCH', $now);

        self::assertFalse($decision->is_eligible());
        self::assertSame(CommercePedagogicalPromotionJoinEligibility::COURSE_MISMATCH, $decision->get_reason());
        self::assertNull($decision->get_context());
    }

    public function test_storefront_projection_exposes_join_contract_without_payment_cta(): void {
        $this->resetAfterTest(true);
        $context = new CommercePedagogicalPromotionJoinContext(
            7, 'M41-READ', 11, 'native_entitlement', 13,
            'm41-read', 'M4.1 Read Model', 17, 2
        );
        $decision = CommercePedagogicalPromotionJoinEligibility::allowed($context);
        $product = new CommerceStorefrontProduct(
            'M41-READ', 'M4.1', '', '', CommerceProductType::COURSE_ACCESS,
            [], [], false, null, [], [], false, 1000, [], 'courses', [], [],
            true, null, [], null, $decision
        );

        $array = $product->to_array();
        self::assertTrue($array['promotionjoin']['eligible']);
        self::assertSame('promotion_join', $array['promotionjoin']['context']['operation']);

        $presented = CommerceStorefrontPresenter::card($product, null);
        self::assertTrue($presented['haspromotionjoincontext']);
        self::assertTrue($presented['promotionjoineligible']);
        self::assertSame('m41-read', $presented['promotionjoinpromotionkey']);
        self::assertSame('native_entitlement', $presented['promotionjoinownershipsource']);
        self::assertFalse($presented['canpurchase']);
        self::assertArrayNotHasKey('promotionjoinactionurl', $presented);
    }
}
