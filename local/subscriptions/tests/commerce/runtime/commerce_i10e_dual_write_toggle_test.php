<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFeatureToggle;

final class commerce_i10e_dual_write_toggle_test extends advanced_testcase {
    public function test_dual_write_is_disabled_when_both_flags_are_disabled(): void {
        $this->resetAfterTest();

        set_config('commerce_native_dual_write_enabled', 0, 'local_subscriptions');
        set_config('commerce_dual_write_enabled', 0, 'local_subscriptions');

        $toggle = new CommerceDualWriteFeatureToggle();

        $this->assertFalse($toggle->is_enabled());
        $this->assertFalse($toggle->has_configuration_mismatch());
    }

    public function test_canonical_i10e_flag_enables_runtime_dual_write(): void {
        $this->resetAfterTest();

        set_config('commerce_native_dual_write_enabled', 1, 'local_subscriptions');
        set_config('commerce_dual_write_enabled', 0, 'local_subscriptions');

        $toggle = new CommerceDualWriteFeatureToggle();

        $this->assertTrue($toggle->is_enabled());
        $this->assertTrue($toggle->canonical_enabled());
        $this->assertTrue($toggle->has_configuration_mismatch());
    }

    public function test_historical_flag_remains_a_compatibility_alias(): void {
        $this->resetAfterTest();

        set_config('commerce_native_dual_write_enabled', 0, 'local_subscriptions');
        set_config('commerce_dual_write_enabled', 1, 'local_subscriptions');

        $toggle = new CommerceDualWriteFeatureToggle();

        $this->assertTrue($toggle->is_enabled());
        $this->assertTrue($toggle->legacy_alias_enabled());
        $this->assertTrue($toggle->has_configuration_mismatch());
    }
}
