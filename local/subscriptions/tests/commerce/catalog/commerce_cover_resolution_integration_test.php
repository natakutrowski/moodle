<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_cover_resolution_integration_test extends \advanced_testcase {
    public function test_customer_surfaces_request_explicit_cover_contexts(): void {
        $root = dirname(__DIR__, 3);

        self::assertStringContainsString(
            "get_cover_url('recommendation')",
            file_get_contents($root . '/classes/commerce/course/recommendation/CommerceCourseRecommendationService.php')
        );
        self::assertStringContainsString(
            "get_cover_url('resources')",
            file_get_contents($root . '/classes/commerce/digital/library/CommerceDigitalLibraryService.php')
        );
        self::assertStringContainsString(
            "get_cover_url('checkout')",
            file_get_contents($root . '/classes/commerce/cart/presentation/CommerceCartPresenter.php')
        );

        $storefront = file_get_contents(
            $root . '/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );
        self::assertStringContainsString(
            "foreach (['product', 'storefront', 'showroom'] as \$context)",
            $storefront
        );
        self::assertStringContainsString(
            '$product->get_cover_url($context)',
            $storefront
        );
    }
}
