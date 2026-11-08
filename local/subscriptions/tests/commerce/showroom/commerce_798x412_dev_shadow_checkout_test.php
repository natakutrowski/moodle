<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
/** Phase 2N: no DEV-only adapter in runtime, native trusted Bridge is in control. */
final class commerce_798x412_dev_shadow_checkout_test extends advanced_testcase {
    public function test_no_dev_only_adapter_is_loaded(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents($root . '/showroom.php');
        self::assertStringNotContainsString('dev_checkout_shadow_post.js', $page);
        self::assertStringNotContainsString('require_login();', $page);
        self::assertStringContainsString('subscription_config::guard_public_access();', $page);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();', $page);
    }
    public function test_checkout_still_server_authoritative(): void {
        $root = dirname(__DIR__, 3);
        $cart = (string)file_get_contents($root . '/cart_action.php');
        self::assertStringContainsString('sesskey', $cart);
        self::assertStringContainsString('priceid', $cart);
        $gate = (string)file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');
        self::assertStringContainsString("public const DESIGN_VERSION = '0.4.13'", $gate);
        self::assertStringContainsString('eligible_published_pilot', $gate);
    }
}
