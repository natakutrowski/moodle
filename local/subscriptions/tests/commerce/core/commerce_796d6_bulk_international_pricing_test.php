<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d6_bulk_international_pricing_test extends advanced_testcase {
    public function test_bulk_service_excludes_bundles_and_existing_target_prices(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/CommerceCatalogFxBulkSuggestionService.php'
        );

        $this->assertStringContainsString(
            'if ($product->is_bundle())',
            $contents
        );
        $this->assertStringContainsString(
            '$hastarget = true;',
            $contents
        );
        $this->assertStringContainsString(
            '$sourceprice === null',
            $contents
        );
    }

    public function test_bulk_admin_flow_has_preview_apply_and_stale_protection(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/currency_prices.php'
        );

        $this->assertStringContainsString(
            'if ($action === \'preview\')',
            $contents
        );
        $this->assertStringContainsString(
            'if ($action === \'apply\')',
            $contents
        );
        $this->assertStringContainsString(
            'price_currency_exists($sku, $currency)',
            $contents
        );
        $this->assertStringContainsString(
            "'source' => 'fx_bulk_assistant'",
            $contents
        );
        $this->assertStringContainsString(
            "optional_param('activate', 0, PARAM_BOOL)",
            $contents
        );
        $this->assertStringContainsString(
            '$rowkey = \'p\' . sha1($sku);',
            $contents
        );
        $this->assertStringContainsString(
            "'name' => 'amounts[' . \$rowkey . ']'",
            $contents
        );
        $this->assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $contents
        );
        $this->assertStringContainsString(
            '->get_editor_data($sku)',
            $contents
        );
        $this->assertStringNotContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_sku(',
            $contents
        );
    }

    public function test_currencies_page_exposes_bulk_tool(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/currencies.php'
        );

        $this->assertStringContainsString(
            '/local/subscriptions/admin/commerce/configuration/currency_prices.php',
            $contents
        );
    }
}
