<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1343_cart_checkout_ux_harmonisation_test extends \advanced_testcase {
    public function test_cart_uses_same_three_reassurance_messages_as_checkout(): void {
        global $CFG;

        $reassurance = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/commerce/payment_reassurance.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        foreach ([
            '{{checkoutsecureencrypted}}',
            '{{checkoutdataprotected}}',
            '{{instantaccesslabel}}',
        ] as $token) {
            self::assertStringContainsString($token, $reassurance);
            self::assertStringContainsString($token, $checkout);
        }

        foreach ([
            'stripeiconurl',
            'alfaiconurl',
            'visaiconurl',
            'mastercardiconurl',
        ] as $legacylogo) {
            self::assertStringNotContainsString($legacylogo, $reassurance);
        }
    }

    public function test_cart_runtime_exposes_checkout_trust_copy(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/cart.php');

        self::assertStringContainsString("'checkoutsecureencrypted' => get_string(", $source);
        self::assertStringContainsString("'checkoutdataprotected' => get_string(", $source);
    }

    public function test_cart_desktop_ratio_is_three_two_and_collapses_at_mobile_breakpoint(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString('commerce-cart__purchase-grid', $template);
        self::assertStringContainsString(
            'grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);',
            $css
        );
        self::assertStringContainsString('@media (max-width: 767.98px)', $css);
    }

    public function test_checkout_has_no_global_provider_selection_subtitle(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $runtime = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringNotContainsString('{{subtitle}}', $template);
        self::assertStringNotContainsString(
            "'subtitle' => get_string('commerce_checkout_subtitle'",
            $runtime
        );
    }
}
