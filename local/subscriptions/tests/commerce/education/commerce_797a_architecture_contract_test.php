<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797a_architecture_contract_test extends advanced_testcase {
    public function test_course_access_mode_is_classic_by_default(): void {
        self::assertSame(
            CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
            CommerceCourseAccessMode::default()
        );
        self::assertSame(
            CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
            CommerceCourseAccessMode::normalise(null)
        );
        self::assertSame(
            [
                CommerceCourseAccessMode::CLASSIC_IMMEDIATE,
                CommerceCourseAccessMode::PROMOTION,
            ],
            CommerceCourseAccessMode::all()
        );
    }

    public function test_unknown_course_access_mode_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommerceCourseAccessMode::normalise('calendar_hardcoded');
    }

    public function test_individual_profiles_keep_legacy_and_lifetime_full_access(): void {
        self::assertTrue(
            CommerceStudentAccessProfile::has_full_course_access(
                CommerceStudentAccessProfile::LEGACY_FULL
            )
        );
        self::assertTrue(
            CommerceStudentAccessProfile::has_full_course_access(
                CommerceStudentAccessProfile::LIFETIME_FULL
            )
        );
        self::assertFalse(
            CommerceStudentAccessProfile::has_full_course_access(
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE
            )
        );
        self::assertTrue(
            CommerceStudentAccessProfile::is_progressive(
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE
            )
        );
    }

    public function test_pedagogical_promotion_lifecycle_is_distinct_from_commercial_promotions(): void {
        self::assertSame(
            ['draft', 'scheduled', 'open', 'full', 'started', 'finished', 'archived'],
            CommercePedagogicalPromotionStatus::all()
        );
        self::assertTrue(
            class_exists(
                \local_subscriptions\commerce\promotion\domain\CommercePromotion::class
            )
        );
        self::assertNotSame(
            \local_subscriptions\commerce\promotion\domain\CommercePromotion::class,
            CommercePedagogicalPromotionStatus::class
        );
    }

    public function test_797a_does_not_change_course_access_grant_semantics(): void {
        $root = dirname(__DIR__, 3);

        $handler = file_get_contents(
            $root
            . '/classes/commerce/fulfillment/native/course/'
            . 'CommerceCourseAccessFulfillmentHandler.php'
        );
        $grant = file_get_contents(
            $root
            . '/classes/commerce/fulfillment/native/course/'
            . 'CommerceCourseAccessGrant.php'
        );

        self::assertIsString($handler);
        self::assertIsString($grant);
        self::assertStringContainsString(
            "public const GRANT_TYPE = 'course_access';",
            $handler
        );
        self::assertStringContainsString("'course_access'", $grant);
        self::assertStringNotContainsString('promotion_progressive', $handler);
    }
}
