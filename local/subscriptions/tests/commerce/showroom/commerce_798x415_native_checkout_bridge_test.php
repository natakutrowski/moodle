<?php

declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomDevCheckoutTestGate as Gate;
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomDevCheckoutTestGate.php');
/** Production-capable native submission; DEV pilot sandbox and OTP guest checkout remain intact. */
final class commerce_798x415_native_checkout_bridge_test extends advanced_testcase {
    public function test_public_pilot_only_accepts_reviewed_bridge_version(): void {
        self::assertSame('0.4.11', Gate::DESIGN_VERSION);
        $args=[8,'published',Gate::KEY,Gate::TEMPLATE,Gate::DEV_ROOT,Gate::DESIGN,
            Gate::DESIGN_VERSION,Gate::PUBLIC_PILOT_SLUGS];
        self::assertTrue(Gate::eligible_published_pilot(...$args));
        $args[6]='0.4.10';
        self::assertFalse(Gate::eligible_published_pilot(...$args));
        $args[6]=Gate::DESIGN_VERSION;
        $args[4]='https://campusfr.fr';
        self::assertFalse(Gate::eligible_published_pilot(...$args));
    }
    public function test_previous_adapter_is_not_loaded_and_guest_is_supported(): void {
        $root=dirname(__DIR__,3);
        $showroom=(string)file_get_contents($root.'/showroom.php');
        self::assertStringNotContainsString('dev_checkout_shadow_post.js',$showroom);
        self::assertStringNotContainsString('require_login();',$showroom);
        self::assertStringContainsString('subscription_config::guard_public_access();',$showroom);
        self::assertStringContainsString('CommerceShowroomDevCheckoutTestGate::require_sandbox();',$showroom);
        self::assertStringContainsString('noindex,nofollow,noarchive',$showroom);
    }
    public function test_cart_action_unchanged(): void {
        $root=dirname(__DIR__,3);
        $cart=(string)file_get_contents($root.'/cart_action.php');
        self::assertStringContainsString('sesskey',$cart);
        self::assertStringContainsString('priceid',$cart);
    }
}
