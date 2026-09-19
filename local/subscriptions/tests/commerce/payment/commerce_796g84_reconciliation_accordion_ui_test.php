<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g84_reconciliation_accordion_ui_test extends advanced_testcase {
    public function test_reconciliation_settings_are_grouped_by_provider(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        foreach ([
            '$renderreconciliationgroup',
            "'stripe_reconciliation_cron_enabled'",
            "'alfa_reconciliation_cron_enabled'",
            "'paypal_reconciliation_cron_enabled'",
            'commerce-payment-reconciliation-provider',
            'commerce-payment-reconciliation-fields',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_provider_icons_are_not_repeated_inside_reconciliation_fields(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            "\$fielddef['hideprovidericon'] = true;",
            $contents
        );
        $this->assertStringContainsString(
            "'stripe.svg'",
            $contents
        );
        $this->assertStringContainsString(
            "'alfa.svg'",
            $contents
        );
        $this->assertStringContainsString(
            "'paypal.svg'",
            $contents
        );
    }

    public function test_reconciliation_save_keys_are_unchanged(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        foreach ([
            'stripe_reconciliation_batch_size',
            'stripe_reconciliation_min_age',
            'stripe_reconciliation_max_age',
            'alfa_reconciliation_batch_size',
            'alfa_reconciliation_min_age',
            'alfa_reconciliation_max_age',
            'paypal_reconciliation_batch_size',
            'paypal_reconciliation_min_age',
            'paypal_reconciliation_max_age',
        ] as $key) {
            $this->assertStringContainsString(
                $key,
                $contents
            );
        }
    }
}
