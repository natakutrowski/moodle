<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

/**
 * J16A.4 contract: role labels are uppercase and the featured offer is emphasised without scaling.
 *
 * @coversNothing
 */
final class commerce_showroom_offer_emphasis_j16a4_test extends advanced_testcase {
    public function test_offer_roles_are_rendered_uppercase(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString(
            '.commerce-showroom-offer__role',
            $css
        );
        self::assertMatchesRegularExpression(
            '/J16A\.4[\s\S]*?text-transform:\s*uppercase;/',
            $css
        );
    }

    public function test_featured_offer_is_larger_without_scale_transform(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString(
            'grid-template-columns: minmax(0, .975fr) minmax(0, 1.05fr) minmax(0, .975fr) !important;',
            $css
        );

        $start = strpos($css, '/* J16A.4');
        $end = strpos($css, '/* J16A.5');
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        self::assertGreaterThan($start, $end);

        $section = substr($css, $start, $end - $start);
        self::assertStringContainsString('.commerce-showroom-offer.is-featured', $section);
        self::assertStringContainsString('transform: none !important;', $section);
        self::assertStringNotContainsString('.commerce-showroom-offer--bundle.is-featured', $section);
    }
}
