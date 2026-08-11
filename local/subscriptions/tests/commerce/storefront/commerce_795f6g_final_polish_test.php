<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

use advanced_testcase;

/** Regression checks for 7.95F6G, aligned with the final F6I renderer. */
final class commerce_795f6g_final_polish_test extends advanced_testcase {
    public function test_lifecycle_password_validation_uses_complete_user_record(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/lifecycle.php'
        );

        $expected = <<<'PHP'
$DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST)
PHP;
        self::assertStringContainsString($expected, $source);
        self::assertStringContainsString(
            "optional_param('adminpassword', '', PARAM_RAW)",
            $source
        );
        self::assertStringNotContainsString(
            'validate_internal_user_password($USER,',
            $source
        );
    }

    public function test_destructive_setting_is_checked_before_password_validation(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/lifecycle.php'
        );

        $guard = <<<'PHP'
if ($force && !get_config('local_subscriptions'
PHP;
        $settingguard = strpos($source, $guard);
        $passwordvalidation = strpos($source, 'validate_internal_user_password($passworduser, $password)');

        self::assertNotFalse($settingguard);
        self::assertNotFalse($passwordvalidation);
        self::assertLessThan($passwordvalidation, $settingguard);
    }

    public function test_type_badge_and_gustave_medallion_have_independent_layout(): void {
        global $CFG;

        $card = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache'
        );
        $badges = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_badges.mustache'
        );
        $css = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString('commerce-product-type-badge', $card);
        self::assertStringNotContainsString('commerce-product-type-badge', $badges);
        self::assertStringContainsString('.commerce-product-type-badge', $css);
        self::assertStringContainsString('width: 4.0rem;', $css);
        self::assertStringContainsString('padding: .45rem 1rem .45rem 4.85rem;', $css);
    }
}
