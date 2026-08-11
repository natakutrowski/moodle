<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

defined('MOODLE_INTERNAL') || die();

/**
 * Z5.10A regression coverage for guarded runtime/read rollback.
 */
final class commerce_runtime_read_rollback_z510a_test extends \advanced_testcase {
    public function test_inspect_targets_legacy_runtime_and_legacy_reads(): void {
        $this->resetAfterTest(true);

        set_config('commerce_runtime_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_read_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_native_fallback_enabled', 1, 'local_subscriptions');
        set_config('commerce_fulfillment_shadow_enabled', 0, 'local_subscriptions');

        $inspection = (new CommerceRuntimeRollbackService())->inspect();

        $this->assertSame(CommerceRuntimeMode::NATIVE, $inspection['before']['runtime_mode']);
        $this->assertSame('native', $inspection['before']['runtime_read_mode']);
        $this->assertSame(CommerceRuntimeMode::LEGACY, $inspection['target']['runtime_mode']);
        $this->assertSame('legacy', $inspection['target']['runtime_read_mode']);
        $this->assertTrue($inspection['target']['native_fallback_enabled']);
        $this->assertFalse($inspection['target']['shadow_enabled']);
        $this->assertFalse($inspection['data_changes']);
    }

    public function test_execute_rolls_native_runtime_and_reads_back_to_legacy(): void {
        $this->resetAfterTest(true);

        set_config('commerce_runtime_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_read_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_native_fallback_enabled', 0, 'local_subscriptions');
        set_config('commerce_fulfillment_shadow_enabled', 1, 'local_subscriptions');

        $result = (new CommerceRuntimeRollbackService())->execute();

        $this->assertTrue($result['verified']);
        $this->assertFalse($result['data_changes']);

        $this->assertSame(CommerceRuntimeMode::NATIVE, $result['before']['runtime_mode']);
        $this->assertSame('native', $result['before']['runtime_read_mode']);

        $this->assertSame(CommerceRuntimeMode::LEGACY, $result['after']['runtime_mode']);
        $this->assertSame('legacy', $result['after']['runtime_read_mode']);
        $this->assertTrue($result['after']['native_fallback_enabled']);
        $this->assertFalse($result['after']['shadow_enabled']);

        $this->assertSame(
            'legacy',
            (string)get_config('local_subscriptions', 'commerce_runtime_mode')
        );
        $this->assertSame(
            'legacy',
            (string)get_config('local_subscriptions', 'commerce_runtime_read_mode')
        );
        $this->assertSame(
            '1',
            (string)get_config('local_subscriptions', 'commerce_runtime_native_fallback_enabled')
        );
        $this->assertSame(
            '0',
            (string)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled')
        );
    }
}
