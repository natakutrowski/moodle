<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\rollout\CommerceStorefrontRollout;

final class commerce_795f5_storefront_rollout_test extends advanced_testcase {
    public function test_subscribe_redirect_is_safe_and_progressive(): void {
        $this->assertFalse(CommerceStorefrontRollout::should_redirect_subscribe(false, false, null));
        $this->assertTrue(CommerceStorefrontRollout::should_redirect_subscribe(true, false, null));
        $this->assertFalse(CommerceStorefrontRollout::should_redirect_subscribe(true, true, null));
        $this->assertFalse(CommerceStorefrontRollout::should_redirect_subscribe(true, false, 32));
    }

    public function test_rollout_is_disabled_by_default(): void {
        $this->resetAfterTest(true);
        unset_config(CommerceStorefrontRollout::CONFIG_ENABLED, 'local_subscriptions');

        $this->assertFalse(CommerceStorefrontRollout::is_enabled());
    }

    public function test_catalogue_course_filter_uses_native_product_type(): void {
        $source = file_get_contents(__DIR__ . '/../../../digital_catalog.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("'value' => 'course_access'", $source);
        $this->assertStringNotContainsString("'value' => 'subscription'", $source);
    }
}
