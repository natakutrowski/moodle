<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackReport;
use local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeConfiguration;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

final class commerce_runtime_rollback_toolkit_test extends advanced_testcase {
    public function test_inspect_is_read_only(): void {
        $this->resetAfterTest();
        (new CommerceRuntimeConfiguration())->set_mode(CommerceRuntimeMode::NATIVE);
        set_config('commerce_runtime_native_fallback_enabled', 0, 'local_subscriptions');

        $result = (new CommerceRuntimeRollbackService())->inspect();

        $this->assertSame(CommerceRuntimeMode::NATIVE, $result['before']['runtime_mode']);
        $this->assertSame(CommerceRuntimeMode::LEGACY, $result['target']['runtime_mode']);
        $this->assertFalse($result['data_changes']);
        $this->assertSame(CommerceRuntimeMode::NATIVE, (new CommerceRuntimeConfiguration())->get_mode());
    }

    public function test_execute_restores_verified_legacy_configuration(): void {
        $this->resetAfterTest();
        (new CommerceRuntimeConfiguration())->set_mode(CommerceRuntimeMode::SHADOW);
        set_config('commerce_runtime_native_fallback_enabled', 0, 'local_subscriptions');

        $result = (new CommerceRuntimeRollbackService())->execute();

        $this->assertTrue($result['verified']);
        $this->assertSame(CommerceRuntimeMode::LEGACY, $result['after']['runtime_mode']);
        $this->assertTrue($result['after']['native_fallback_enabled']);
        $this->assertFalse($result['after']['shadow_enabled']);
        $this->assertFalse($result['data_changes']);
    }

    public function test_report_is_written_atomically(): void {
        $this->resetAfterTest();
        $path = make_request_directory() . '/rollback.json';
        $report = CommerceRuntimeRollbackReport::build('dry_run', 'preview', ['data_changes' => false]);

        CommerceRuntimeRollbackReport::write_atomic($path, $report);

        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('commerce_runtime_rollback', $decoded['operation']);
        $this->assertSame('preview', $decoded['status']);
        $this->assertFalse($decoded['result']['data_changes']);
    }
}
