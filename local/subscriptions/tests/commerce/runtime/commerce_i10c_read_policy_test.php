<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\read\policy\CommerceReadConsumer;
use local_subscriptions\commerce\read\policy\CommerceReadPolicy;

final class commerce_i10c_read_policy_test extends advanced_testcase {
    public function test_native_consumers_are_disabled_by_default(): void {
        $this->resetAfterTest();

        $policy = new CommerceReadPolicy();

        $this->assertFalse($policy->is_native_enabled(CommerceReadConsumer::CRM));
        $this->assertTrue($policy->is_legacy_fallback_enabled());
        $this->assertFalse($policy->is_shadow_enabled());
    }

    public function test_policy_reads_consumer_flags(): void {
        $this->resetAfterTest();
        set_config('commerce_native_crm_reads_enabled', 1, 'local_subscriptions');
        set_config('commerce_native_shadow_compare_enabled', 1, 'local_subscriptions');

        $policy = new CommerceReadPolicy();

        $this->assertTrue($policy->is_native_enabled(CommerceReadConsumer::CRM));
        $this->assertTrue($policy->is_shadow_enabled());
    }
}
