<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_unfinished_checkout_polish_n67_test extends advanced_testcase {
    public function test_unfinished_checkout_uses_business_labels_and_provider_logos(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/unfinished-checkouts/index.php'
        );

        foreach ([
            'commerce_guest_crm_search_filters_title',
            'commerce_guest_crm_account_id',
            'commerce_guest_crm_checkout_id',
            'commerce_guest_crm_resume_active',
            'commerce_guest_crm_provider_state',
            'commerce_guest_crm_help_legend_title',
            'Provider::icon_url',
            'CommercePurchasePresentation::technical_status_label',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            "s((string)\$row['source_status'])",
            $source
        );
        self::assertStringNotContainsString(
            "s((string)\$payment->providerlivestatus)",
            $source
        );
    }

    public function test_sales_has_provider_column_visible_by_default(): void {
        $root = dirname(__DIR__, 3);
        $sales = file_get_contents(
            $root . '/admin/commerce/purchases/index.php'
        );

        self::assertStringContainsString(
            "'products', 'provider', 'amount'",
            $sales
        );
        self::assertStringContainsString(
            "'provider' => get_string('commerce_purchase_provider'",
            $sales
        );
        self::assertStringContainsString(
            "'provider' => html_writer::div(",
            $sales
        );
        self::assertStringContainsString(
            "'aria-label' => \$providername",
            $sales
        );
    }

    public function test_checkout_polish_does_not_bump_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
