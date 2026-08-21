<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;

final class commerce_showroom_pricing_validity_m14_2_test extends advanced_testcase {
    public function test_product_promotion_accepts_paris_date_and_time(): void {
        global $CFG;

        $pricing = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/pricing.php'
        );

        self::assertIsString($pricing);
        self::assertStringContainsString(
            '\\core_date::get_user_timezone_object()',
            $pricing
        );
        self::assertStringContainsString(
            "'Y-m-d\\TH:i'",
            $pricing
        );
        self::assertStringContainsString(
            "'type' => 'datetime-local'",
            $pricing
        );
        self::assertStringContainsString(
            'CommerceProductPromotionService',
            $pricing
        );
    }

    public function test_legacy_date_only_values_keep_end_of_day_semantics(): void {
        global $CFG;

        $editor = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/admin/'
            . 'CommerceStorefrontPageEditor.php'
        );

        self::assertIsString($editor);
        self::assertStringContainsString(
            'Historical Storefront promotion data is preserved',
            $editor
        );
        self::assertStringContainsString(
            "\$existingmerchandising['promotions']",
            $editor
        );
        self::assertStringContainsString(
            "\$existingstorefront['merchandising']['promotions']",
            $editor
        );
    }

    public function test_showroom_uses_best_bundle_discount_and_renders_validity(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/'
            . 'CommerceShowroomProductResolver.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/showroom/offer.mustache'
        );
        $pricing = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/pricing.php'
        );

        self::assertIsString($resolver);
        self::assertIsString($template);
        self::assertIsString($pricing);
        self::assertStringContainsString(
            '$bestcompare = max($candidates);',
            $resolver
        );
        self::assertStringContainsString(
            '$configuredcompare',
            $resolver
        );
        self::assertStringContainsString(
            '$combinedamount',
            $resolver
        );
        self::assertStringContainsString(
            "'haspromotionend'",
            $resolver
        );
        self::assertStringContainsString(
            'commerce-showroom-offer__validity',
            $template
        );
        self::assertStringContainsString(
            "'type' => 'datetime-local'",
            $pricing
        );
    }
}
