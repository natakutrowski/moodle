<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/** H2 structural certification for the thin Unified Checkout UI. */
final class commerce_795h2_unified_checkout_ui_test extends \advanced_testcase {
    public function test_checkout_entrypoint_is_thin_and_uses_runtime_prepare(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');

        self::assertIsString($source);
        self::assertStringContainsString('CommerceCheckoutRuntimeFactory::create()->prepare(', $source);
        self::assertStringContainsString("render_from_template('local_subscriptions/checkout/page'", $source);
        self::assertStringNotContainsString('CommerceCartCalculator', $source);
        self::assertStringContainsString('CommerceGuestCheckoutSessionRepository', $source);
    }

    public function test_checkout_action_is_csrf_protected_and_launches_provider_through_runtime(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('require_login();', $source);
        self::assertStringContainsString('require_sesskey();', $source);
        self::assertStringContainsString('CommerceCheckoutIdentityResolver::create()->resolve(', $source);
        self::assertStringContainsString('CommerceGuestCartRecoveryService::create()->recover_current(', $source);
        self::assertStringContainsString('CommerceCheckoutRuntimeFactory::create()->launch(', $source);
    }

    public function test_cart_links_to_the_unified_checkout_entrypoint(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/cart.php');

        self::assertIsString($source);
        self::assertStringContainsString('/local/subscriptions/commerce_checkout.php', $source);
    }
}
