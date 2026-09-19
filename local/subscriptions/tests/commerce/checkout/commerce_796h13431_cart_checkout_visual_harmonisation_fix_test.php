<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13431_cart_checkout_visual_harmonisation_fix_test extends \advanced_testcase {
    public function test_cart_keeps_two_columns_until_mobile_breakpoint(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        self::assertStringContainsString('grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);', $css);
        self::assertStringContainsString('@media (max-width: 767.98px)', $css);
        self::assertStringNotContainsString('minmax(21rem, 7fr)', $css);
    }

    public function test_cart_loads_checkout_trust_strip_styles(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/cart.php');
        self::assertStringContainsString('styles/checkout_express_wallets.css', $source);
    }

    public function test_checkout_payment_heading_has_no_provider_description(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $runtime = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        self::assertStringNotContainsString('{{paymentdescription}}', $template);
        self::assertStringNotContainsString("'paymentdescription' =>", $runtime);
        self::assertStringContainsString('class="h3 mb-0">{{paymenttitle}}', $template);
    }

    public function test_cart_and_checkout_share_the_same_trust_component_classes(): void {
        global $CFG;
        $reassurance = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/commerce/payment_reassurance.mustache');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        foreach (['commerce-checkout-trust-strip','commerce-checkout-trust-strip__item'] as $class) {
            self::assertStringContainsString($class, $reassurance);
            self::assertStringContainsString($class, $checkout);
        }
    }
}
