<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1038_test extends advanced_testcase {
    public function test_engine_screen_exposes_active_runtime_and_read_controls(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/configuration/section.php'
        );

        foreach ([
            'commerce_checkout_enabled',
            'commerce_fulfillment_enabled',
            'commerce_runtime_mode',
            'commerce_runtime_native_fallback_enabled',
            'commerce_runtime_read_mode',
            'commerce_runtime_read_strict',
            'commerce_native_reconciliation_enabled',
            'commerce_native_repair_enabled',
        ] as $key) {
            $this->assertStringContainsString(
                "\$field('" . $key . "'",
                $source
            );
        }
    }

    public function test_shadow_flag_is_derived_from_runtime_mode(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/configuration/section.php'
        );
        $runtime = file_get_contents(
            $root . '/classes/commerce/runtime/switching/CommerceRuntimeConfiguration.php'
        );

        $this->assertStringNotContainsString(
            "\$field('commerce_fulfillment_shadow_enabled'",
            $source
        );
        $this->assertStringContainsString(
            '(new CommerceRuntimeConfiguration())->set_mode((string)$clean);',
            $source
        );
        $this->assertStringContainsString(
            "set_config('commerce_fulfillment_shadow_enabled', \$mode === CommerceRuntimeMode::SHADOW ? 1 : 0, 'local_subscriptions');",
            $runtime
        );
    }

    public function test_repair_cannot_be_enabled_without_reconciliation(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            '$submittedrepair && !$submittedreconciliation',
            $source
        );
        $this->assertStringContainsString(
            'commerce_configuration_repair_requires_reconciliation',
            $source
        );
    }

    public function test_runtime_flags_have_real_consumers(): void {
        $root = dirname(__DIR__, 3);

        $dispatcher = file_get_contents(
            $root . '/classes/commerce/runtime/switching/CommerceRuntimeDispatcher.php'
        );
        $reads = file_get_contents(
            $root . '/classes/commerce/runtime/read/CommerceRuntimeReadFeatureToggle.php'
        );
        $reconciliation = file_get_contents(
            $root . '/classes/commerce/reconciliation/CommerceReconciliationPolicy.php'
        );

        $this->assertStringContainsString(
            'native_fallback_enabled()',
            $dispatcher
        );
        $this->assertStringContainsString(
            'commerce_runtime_read_mode',
            $reads
        );
        $this->assertStringContainsString(
            'commerce_runtime_read_strict',
            $reads
        );
        $this->assertStringContainsString(
            'commerce_native_reconciliation_enabled',
            $reconciliation
        );
        $this->assertStringContainsString(
            'commerce_native_repair_enabled',
            $reconciliation
        );
    }

    public function test_n1038_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        $this->assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
