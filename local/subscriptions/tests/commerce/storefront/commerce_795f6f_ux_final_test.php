<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

use advanced_testcase;
use local_subscriptions\commerce\catalog\admin\CommerceProductLifecycleService;

/** Regression checks for 7.95F6F. */
final class commerce_795f6f_ux_final_test extends advanced_testcase {
    public function test_lifecycle_service_counts_native_purchase_reference(): void {
        global $DB;
        $this->resetAfterTest();

        $service = new CommerceProductLifecycleService($DB);
        $reflection = new \ReflectionClass($service);
        self::assertTrue($reflection->hasMethod('dependency_counts'));
        self::assertTrue($reflection->hasMethod('can_delete_without_sales'));
    }

    public function test_destructive_delete_setting_defaults_to_disabled(): void {
        $this->resetAfterTest();
        unset_config('commerce_allow_destructive_product_delete', 'local_subscriptions');
        self::assertFalse((bool)get_config('local_subscriptions', 'commerce_allow_destructive_product_delete'));
    }

    public function test_gustave_badge_uses_independent_medallion(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');
        self::assertStringContainsString('.commerce-product-badge__gustave-medallion', $css);
        self::assertStringContainsString('position: absolute', $css);
    }
}
