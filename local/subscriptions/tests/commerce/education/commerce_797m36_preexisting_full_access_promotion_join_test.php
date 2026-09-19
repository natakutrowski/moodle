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
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;

final class commerce_797m36_preexisting_full_access_promotion_join_test extends advanced_testcase {
    private function promotion(int $courseid, int $now): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm36-promo',
                'M3.6 Promotion',
                $courseid,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                10,
                null,
                null,
                $now,
                $now
            )
        );
    }

    private function product(string $sku): CommerceProduct {
        global $DB;

        return (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $sku
        ));
    }

    private function grant(
        string $sku,
        int $courseid,
        int $userid,
        string $email,
        string $purchase
    ): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-' . strtolower($sku) . '-' . $userid,
            $purchase,
            'item-' . strtolower($sku) . '-' . $userid,
            $sku,
            CommerceCourseAccessFulfillmentHandler::GRANT_TYPE,
            'course:' . $courseid . ':full',
            1,
            $userid,
            $email,
            time(),
            null
        );
    }

    public function test_preexisting_full_course_access_is_not_downgraded_when_joining_promotion(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->promotion((int)$course->id, $now);
        $product = $this->product('M36-FR');

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            $now
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            5,
            null,
            $now
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$this->grant(
                'M36-FR',
                (int)$course->id,
                (int)$user->id,
                $user->email,
                'purchase-m36-preexisting'
            )],
            $now,
            null,
            [(int)$user->id . ':' . (int)$course->id => true]
        );

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$course->id,
            (int)$user->id
        );

        self::assertNotNull($access);
        self::assertSame(
            CommerceStudentAccessProfile::LIFETIME_FULL,
            $access->get_profile()
        );
        self::assertSame((int)$promotion->get_id(), $access->get_promotion_id());
        self::assertTrue(
            CommercePedagogicalParticipationRepository::create($DB)->is_active(
                (int)$promotion->get_id(),
                (int)$user->id
            )
        );
    }

    public function test_existing_lifetime_full_user_still_gets_promotion_participation(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->promotion((int)$course->id, $now);
        $product = $this->product('M36-LIFE');

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            $now
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            5,
            null,
            $now
        );

        CommerceStudentCourseAccessRepository::create($DB)->save(
            new CommerceStudentCourseAccess(
                null,
                (int)$course->id,
                (int)$user->id,
                null,
                CommerceStudentAccessProfile::LIFETIME_FULL,
                null,
                null,
                $now - DAYSECS,
                $now - DAYSECS
            )
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$this->grant(
                'M36-LIFE',
                (int)$course->id,
                (int)$user->id,
                $user->email,
                'purchase-m36-lifetime'
            )],
            $now
        );

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$course->id,
            (int)$user->id
        );

        self::assertNotNull($access);
        self::assertSame(
            CommerceStudentAccessProfile::LIFETIME_FULL,
            $access->get_profile()
        );
        self::assertSame((int)$promotion->get_id(), $access->get_promotion_id());
        self::assertTrue(
            CommercePedagogicalParticipationRepository::create($DB)->is_active(
                (int)$promotion->get_id(),
                (int)$user->id
            )
        );
    }

    public function test_progressive_profile_is_not_upgraded_by_late_replay_snapshot(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->promotion((int)$course->id, $now);
        $product = $this->product('M48-FRESH');

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            $now
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            5,
            null,
            $now
        );

        $grant = $this->grant(
            'M48-FRESH',
            (int)$course->id,
            (int)$user->id,
            $user->email,
            'purchase-m48-fresh'
        );
        $orchestrator = CommercePedagogicalPurchaseOrchestrator::create($DB);

        // First fulfillment is a genuinely fresh promotion customer.
        $orchestrator->apply([$grant], $now);

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$course->id,
            (int)$user->id
        );
        self::assertNotNull($access);
        self::assertSame(
            CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
            $access->get_profile()
        );

        // Simulate a late callback whose Moodle snapshot now sees the enrolment
        // created by the first fulfillment. That must not upgrade the already
        // classified progressive relation to lifetime_full.
        $orchestrator->apply(
            [$grant],
            $now + 1,
            null,
            [(int)$user->id . ':' . (int)$course->id => true]
        );

        $replayed = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$course->id,
            (int)$user->id
        );
        self::assertNotNull($replayed);
        self::assertSame(
            CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
            $replayed->get_profile()
        );
    }

    public function test_paid_purchase_serialises_callbacks_before_preexisting_snapshot(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/fulfillment/native/checkout/CommerceNativePaidPurchaseCompleter.php'
        );

        $lockpos = strpos(
            $source,
            "lock_config::get_lock_factory(\n            'local_subscriptions_commerce_paid_purchase'"
        );
        $snapshotpos = strpos(
            $source,
            '$preexistingfullcourseaccess ='
        );
        $releasepos = strpos(
            $source,
            '$lock->release();'
        );

        self::assertNotFalse($lockpos);
        self::assertNotFalse($snapshotpos);
        self::assertNotFalse($releasepos);
        self::assertLessThan($snapshotpos, $lockpos);
        self::assertGreaterThan($snapshotpos, $releasepos);
    }

    public function test_paid_purchase_snapshots_preexisting_enrolment_before_native_fulfillment(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/fulfillment/native/checkout/CommerceNativePaidPurchaseCompleter.php'
        );

        $snapshotpos = strpos(
            $source,
            '$preexistingfullcourseaccess ='
        );
        $nativepos = strpos(
            $source,
            '$orchestrator->execute_purchase('
        );
        $bridgepos = strpos(
            $source,
            'CommercePedagogicalPurchaseOrchestrator::create($this->db)->apply('
        );

        self::assertNotFalse($snapshotpos);
        self::assertNotFalse($nativepos);
        self::assertNotFalse($bridgepos);
        self::assertLessThan($nativepos, $snapshotpos);
        self::assertLessThan($bridgepos, $nativepos);
        self::assertStringContainsString(
            '$preexistingfullcourseaccess',
            substr($source, $bridgepos, 500)
        );
    }
}
