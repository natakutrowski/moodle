<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\readiness\CommerceProductionReadinessAuditor;

final class commerce_production_readiness_auditor_test extends \advanced_testcase {
    public function test_auditor_uses_current_dependency_injection_contract(): void {
        global $CFG, $DB;

        $auditor = new CommerceProductionReadinessAuditor(
            $DB,
            $CFG->dirroot,
            $CFG->dirroot . '/local/subscriptions'
        );

        $this->assertInstanceOf(CommerceProductionReadinessAuditor::class, $auditor);
        $this->assertTrue(method_exists($auditor, 'audit'));
    }

    public function test_runtime_mode_registry_exposes_all_dispatcher_modes(): void {
        $this->assertSame([
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode::LEGACY,
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode::SHADOW,
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode::NATIVE,
        ], \local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode::all());
        $this->assertTrue(method_exists(
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeDispatcher::class,
            'checkout_completed'
        ));
    }

    public function test_rollback_service_declares_safe_legacy_target_without_data_changes(): void {
        $inspection = (new \local_subscriptions\commerce\runtime\rollback\CommerceRuntimeRollbackService())->inspect();

        $this->assertSame(
            \local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode::LEGACY,
            $inspection['target']['runtime_mode']
        );
        $this->assertTrue($inspection['target']['native_fallback_enabled']);
        $this->assertFalse($inspection['target']['shadow_enabled']);
        $this->assertFalse($inspection['data_changes']);
    }

    public function test_auditor_and_cli_are_read_only(): void {
        global $CFG;

        $paths = [
            $CFG->dirroot . '/local/subscriptions/classes/commerce/readiness/CommerceProductionReadinessAuditor.php',
            $CFG->dirroot . '/local/subscriptions/cli/commerce/prod_ready.php',
        ];
        foreach ($paths as $path) {
            $contents = (string)file_get_contents($path);
            $this->assertStringNotContainsString('set_config(', $contents);
            $this->assertStringNotContainsString('insert_record(', $contents);
            $this->assertStringNotContainsString('update_record(', $contents);
            $this->assertStringNotContainsString('delete_records(', $contents);
        }
    }
}
