<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromiseService;
use local_subscriptions\commerce\customer\course\CommerceCustomerCourseLearningStatusService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalGroupLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupReplayBridgeService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupXpBridgeService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePaidPurchaseCompleter;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePurchaseGrantPlanner;

/** Targeted release-gate smoke checks before the 7.97 real E2E campaign. */
final class commerce_797m81_release_preflight_test extends advanced_testcase {
    public function test_797_schema_and_release_marker_are_present(): void {
        global $CFG;

        $version = file_get_contents($CFG->dirroot . '/local/subscriptions/version.php');
        $install = file_get_contents($CFG->dirroot . '/local/subscriptions/db/install.xml');

        self::assertIsString($version);
        self::assertIsString($install);
        self::assertMatchesRegularExpression(
            '/\\$plugin->version\\s*=\\s*(\\d+)\\s*;/',
            $version
        );
        preg_match('/\\$plugin->version\\s*=\\s*(\\d+)\\s*;/', $version, $matches);
        self::assertGreaterThanOrEqual(2026091501, (int)($matches[1] ?? 0));

        foreach ([
            'local_subs_commerce_ped_promo',
            'local_subs_commerce_ped_offer',
            'local_subs_commerce_ped_cal',
            'local_subs_commerce_ped_group',
            'local_subs_commerce_ped_gmem',
            'local_subs_commerce_ped_access',
            'local_subs_commerce_ped_resv',
            'local_subs_commerce_ped_xp',
            'local_subs_commerce_ped_join',
        ] as $table) {
            self::assertStringContainsString('TABLE NAME="' . $table . '"', $install);
        }
    }

    public function test_critical_797_services_are_autoloadable(): void {
        foreach ([
            CommercePedagogicalPromotionJoinEligibilityService::class,
            CommercePedagogicalSeatReservationService::class,
            CommercePedagogicalXpRepository::class,
            CommercePedagogicalGroupLeaderboardService::class,
            CommercePedagogicalLevelupXpBridgeService::class,
            CommercePedagogicalLevelupReplayBridgeService::class,
            CommerceCustomerAccessPromiseService::class,
            CommerceCustomerCourseLearningStatusService::class,
            CommerceNativePurchaseGrantPlanner::class,
            CommerceNativePaidPurchaseCompleter::class,
        ] as $class) {
            self::assertTrue(class_exists($class), $class . ' must autoload before E2E certification.');
        }
    }

    public function test_customer_and_admin_surfaces_required_for_e2e_are_present(): void {
        global $CFG;

        foreach ([
            '/local/subscriptions/digital_catalog.php',
            '/local/subscriptions/storefront_product.php',
            '/local/subscriptions/cart.php',
            '/local/subscriptions/cart_print.php',
            '/local/subscriptions/commerce_checkout.php',
            '/local/subscriptions/order_result.php',
            '/local/subscriptions/my_purchases.php',
            '/local/subscriptions/admin/commerce/education/promotions.php',
            '/local/subscriptions/admin/commerce/education/promotion_edit.php',
            '/local/subscriptions/admin/commerce/education/offers.php',
            '/local/subscriptions/admin/commerce/education/calendar.php',
            '/local/subscriptions/admin/commerce/education/groups.php',
            '/local/subscriptions/admin/commerce/education/participants.php',
        ] as $relative) {
            self::assertFileExists($CFG->dirroot . $relative);
        }
    }

    public function test_native_fulfillment_keeps_multi_item_bundle_and_promotion_join_paths(): void {
        global $CFG;

        $planner = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/fulfillment/native/checkout/'
            . 'CommerceNativePurchaseGrantPlanner.php'
        );

        self::assertIsString($planner);
        self::assertStringContainsString('CommerceBundleExpansionService', $planner);
        self::assertStringContainsString('$expandeditems = $expander->expand(', $planner);
        self::assertStringContainsString('CommercePedagogicalPromotionJoinOperation::OPERATION', $planner);
        self::assertStringContainsString('foreach ($items as $item)', $planner);
    }
}
