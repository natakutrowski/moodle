<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Certifies the navigation contract between Storefront, Showroom and recommendations.
 *
 * Discovery cards may resolve to a Showroom, while an explicit Showroom
 * "details" CTA must always use a direct Storefront URL and preserve a safe
 * Showroom origin context for the contextual back link.
 */
final class commerce_storefront_navigation_consistency_test extends advanced_testcase {
    public function test_showroom_details_use_direct_storefront_and_tracking_context(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/showroom/'
                . 'CommerceShowroomProductResolver.php'
        );

        self::assertStringContainsString(
            'CommerceStorefrontUrlResolver::direct_storefront(',
            $source
        );
        self::assertStringContainsString(
            "'source' => 'showroom'",
            $source
        );
        self::assertStringContainsString(
            "'showroom' => \$showroomkey",
            $source
        );
        self::assertStringContainsString(
            "'showroomoffer' => \$role",
            $source
        );
    }

    public function test_discovery_resolver_rejects_same_showroom_loop(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/catalog/presentation/'
                . 'CommerceProductDiscoveryUrlResolver.php'
        );

        self::assertStringContainsString(
            '$showroomkey !== $currentshowroom',
            $source
        );
        self::assertStringContainsString(
            'return self::storefront(',
            $source
        );
    }

    public function test_storefront_recommendations_keep_discovery_semantics(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/presentation/'
                . 'CommerceStorefrontPresenter.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/recommendation/'
                . 'CommerceStorefrontRecommendationService.php'
        );

        self::assertStringContainsString(
            "'detailsurl' => CommerceStorefrontUrlResolver::details(",
            $presenter
        );
        self::assertStringContainsString(
            '!$product->is_owned()',
            $service
        );
    }

    public function test_owned_customer_surfaces_use_direct_storefront_not_discovery(): void {
        global $CFG;

        $purchases = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/output/my_purchases/'
                . 'CurrentPresentationRenderer.php'
        );
        $library = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/digital/library/'
                . 'CommerceDigitalLibraryService.php'
        );

        self::assertStringContainsString(
            'CommerceCustomerPublicUrlResolver::storefront(',
            $purchases
        );
        self::assertStringContainsString(
            'CommerceStorefrontUrlResolver::direct_storefront(',
            $library
        );
    }

    public function test_contextual_back_navigation_accepts_only_known_origins(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/navigation/'
                . 'CommerceStorefrontReturnNavigationResolver.php'
        );

        self::assertStringContainsString(
            "if (\n            \$source === 'showroom'",
            $source
        );
        self::assertStringContainsString(
            'CommerceProductDiscoveryUrlResolver::is_published_showroom($showroomkey)',
            $source
        );
        self::assertStringContainsString(
            "if (\$from === 'shop')",
            $source
        );
        self::assertStringNotContainsString(
            'redirect(',
            $source
        );
    }
}
