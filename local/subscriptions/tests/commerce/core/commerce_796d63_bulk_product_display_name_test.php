<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d63_bulk_product_display_name_test extends advanced_testcase {
    public function test_bulk_preview_resolves_business_name_with_product_name_resolver(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/currency_prices.php'
        );

        $this->assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $contents
        );
        $this->assertStringContainsString(
            '->get_editor_data($sku)',
            $contents
        );
        $this->assertStringContainsString(
            '->get_product()',
            $contents
        );
        $this->assertStringContainsString(
            '->get_id()',
            $contents
        );
    }
}
