<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

final class commerce_checkout_chromeless_j15h1d_test extends \advanced_testcase {
    public function test_unified_checkout_uses_the_chromeless_commerce_shell(): void {
        global $CFG;

        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        $styles = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');

        self::assertStringContainsString("add_body_class('commerce-chromeless-page')", $checkout);
        self::assertStringContainsString('body#page-local-subscriptions-commerce_checkout .page-banner-area', $styles);
        self::assertStringContainsString('body#page-local-subscriptions-commerce_checkout #page-header', $styles);
    }
}
