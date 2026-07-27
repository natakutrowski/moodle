<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\runtime\switching\CommerceRuntimeConfiguration;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

final class commerce_runtime_switch_test extends \advanced_testcase {
    public function test_invalid_mode_falls_back_to_legacy(): void {
        $this->assertSame(CommerceRuntimeMode::LEGACY, CommerceRuntimeMode::normalize('invalid'));
    }

    public function test_configuration_switches_shadow_flag_consistently(): void {
        $this->resetAfterTest();
        $config = new CommerceRuntimeConfiguration();
        $config->set_mode(CommerceRuntimeMode::SHADOW);
        $this->assertSame(CommerceRuntimeMode::SHADOW, $config->get_mode());
        $this->assertTrue((bool)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled'));
        $config->set_mode(CommerceRuntimeMode::LEGACY);
        $this->assertFalse((bool)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled'));
    }

    public function test_native_fallback_is_configurable(): void {
        $this->resetAfterTest();
        set_config('commerce_runtime_native_fallback_enabled', 1, 'local_subscriptions');
        $this->assertTrue((new CommerceRuntimeConfiguration())->native_fallback_enabled());
    }
}
