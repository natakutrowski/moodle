<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_offer_pricing_j13h25_test extends \advanced_testcase {
    public function test_bundle_uses_component_sum_only_when_it_is_cheaper(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/'
            . 'CommerceShowroomProductResolver.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertIsString($resolver);
        self::assertIsString($template);
        self::assertIsString($css);
        self::assertStringContainsString('apply_bundle_merchandising_price', $resolver);
        self::assertStringContainsString('$bundleamount >= $combinedamount', $resolver);
        self::assertStringContainsString('CommercePurchasePresentation::money(', $resolver);
        self::assertStringContainsString('commerce_storefront_discount_percentage', $resolver);
        self::assertStringContainsString('commerce-showroom-offer__pricing-promotion', $template);
        self::assertStringContainsString('transform: translate(50%, -50%)', $css);
        self::assertStringContainsString('margin-top: 1.35rem', $css);
    }
}
