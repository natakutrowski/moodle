<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/**
 * J16A.4 contract: role labels are uppercase and the Bundle is emphasised without scaling.
 *
 * @coversNothing
 */
final class commerce_showroom_offer_emphasis_j16a4_test extends advanced_testcase {
    public function test_offer_roles_are_rendered_uppercase(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString(
            '.commerce-showroom-offer--bundle .commerce-showroom-offer__role',
            $css
        );
        self::assertMatchesRegularExpression(
            '/J16A\.4[\s\S]*?text-transform:\s*uppercase;/',
            $css
        );
    }

    public function test_featured_bundle_is_larger_without_scale_transform(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString(
            'grid-template-columns: minmax(0, .975fr) minmax(0, 1.05fr) minmax(0, .975fr) !important;',
            $css
        );
        self::assertMatchesRegularExpression(
            '/J16A\.4[\s\S]*?\.commerce-showroom-offer--bundle\.is-featured[\s\S]*?transform:\s*none\s*!important;/',
            $css
        );
    }
}
