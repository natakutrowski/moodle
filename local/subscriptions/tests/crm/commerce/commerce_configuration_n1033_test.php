<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1033_test extends advanced_testcase {
    public function test_payment_reconciliation_exposes_age_windows_for_both_providers(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertIsString($source);
        foreach ([
            'stripe_reconciliation_min_age',
            'stripe_reconciliation_max_age',
            'alfa_reconciliation_min_age',
            'alfa_reconciliation_max_age',
        ] as $key) {
            $this->assertStringContainsString("\$field('" . $key . "'", $source);
        }
    }

    public function test_payment_integrity_settings_belong_to_payments_section(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertStringContainsString(
            "'commerce_configuration_group_payment_integrity' => [",
            $source
        );
        $this->assertSame(1, substr_count($source, "\$field('payments_lock_strict'"));
        $this->assertSame(1, substr_count($source, "\$field('payments_mismatch_tolerance_cents'"));
    }

    public function test_default_user_language_uses_component_then_setting_name(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            '/checkout.php',
            '/payment/create_session.php',
            '/payment/digital_create_session.php',
        ] as $file) {
            $source = file_get_contents($root . $file);
            $this->assertStringContainsString(
                "get_config('local_subscriptions', 'defaultuserlang')",
                $source
            );
            $this->assertStringNotContainsString(
                "get_config('defaultuserlang','local_subscriptions')",
                $source
            );
            $this->assertStringNotContainsString(
                "get_config('defaultuserlang', 'local_subscriptions')",
                $source
            );
        }
    }

    public function test_n103_technical_name_contract_matches_compact_display(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/tests/crm/commerce/commerce_configuration_n103_test.php'
        );
        $this->assertStringContainsString(
            "local_subscriptions | ",
            $source
        );
        $this->assertStringNotContainsString(
            "get_config(\\'local_subscriptions\\', \\'",
            $source
        );
    }
}
