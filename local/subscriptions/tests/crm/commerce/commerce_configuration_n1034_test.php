<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1034_test extends advanced_testcase {
    public function test_localisation_groups_active_runtime_settings_by_business_role(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        foreach ([
            'commerce_configuration_group_commerce_availability',
            'commerce_configuration_group_languages',
            'commerce_configuration_group_currencies',
        ] as $key) {
            $this->assertStringContainsString($key, $source);
        }

        $this->assertStringContainsString(
            "\$field('commerce_enabled_currencies'",
            $source
        );
        $this->assertStringContainsString(
            "'multicheck_csv'",
            $source
        );
    }

    public function test_currency_registry_and_display_setting_have_live_consumers(): void {
        $root = dirname(__DIR__, 3);
        $registry = file_get_contents(
            $root . '/classes/commerce/catalog/currency/CommerceCurrencyRegistry.php'
        );
        $checkout = file_get_contents($root . '/checkout.php');

        $this->assertStringContainsString(
            "get_config('local_subscriptions', 'commerce_enabled_currencies')",
            $registry
        );
        $this->assertStringContainsString(
            "get_config('local_subscriptions','display_currency_symbols')",
            $checkout
        );
    }

    public function test_checkout_only_contains_followup_and_guest_cleanup_settings(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $checkoutstart = strpos($source, "'checkout' => [");
        $communications = strpos($source, "'communications' => [", $checkoutstart);
        $checkout = substr($source, $checkoutstart, $communications - $checkoutstart);

        foreach ([
            'expire_pending_after_minutes',
            'reminder1_after_minutes',
            'reminder2_after_minutes',
            'guest_checkout_cleanup_enabled',
            'guest_checkout_cleanup_age_days',
            'guest_checkout_cleanup_batch_size',
        ] as $key) {
            $this->assertStringContainsString($key, $checkout);
        }

        $this->assertStringNotContainsString('payments_lock_strict', $checkout);
        $this->assertStringNotContainsString('payments_mismatch_tolerance_cents', $checkout);
    }

    public function test_payment_integrity_settings_are_really_in_payments(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $payments = substr(
            $source,
            strpos($source, "'payments' => ["),
            strpos($source, "'localisation' => [") - strpos($source, "'payments' => [")
        );

        $this->assertStringContainsString('payments_lock_strict', $payments);
        $this->assertStringContainsString('payments_mismatch_tolerance_cents', $payments);
    }

    public function test_checkout_runtime_uses_all_exposed_cleanup_and_followup_settings(): void {
        $root = dirname(__DIR__, 3);
        $followup = file_get_contents(
            $root . '/classes/commerce/task/job/PaymentFollowupJob.php'
        );
        $cleanup = file_get_contents(
            $root . '/classes/task/cleanup_abandoned_guest_checkouts_task.php'
        );

        foreach ([
            'expire_pending_after_minutes',
            'reminder1_after_minutes',
            'reminder2_after_minutes',
        ] as $key) {
            $this->assertStringContainsString($key, $followup);
        }

        foreach ([
            'guest_checkout_cleanup_enabled',
            'guest_checkout_cleanup_age_days',
            'guest_checkout_cleanup_batch_size',
        ] as $key) {
            $this->assertStringContainsString($key, $cleanup);
        }
    }

    public function test_storefront_no_longer_duplicates_global_currency_symbol_setting(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $storefrontstart = strpos($source, "'storefront' => [");
        $enginestart = strpos($source, "'engine' => [", $storefrontstart);
        $storefront = substr($source, $storefrontstart, $enginestart - $storefrontstart);

        $this->assertStringNotContainsString('display_currency_symbols', $storefront);
    }
}
