<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d61_bulk_product_name_resolver_test extends advanced_testcase {
    public function test_bulk_tool_uses_existing_native_id_resolver_api(): void {
        global $CFG;

        $this->assertTrue(
            method_exists(CommerceCatalogProductNameResolver::class, 'resolve_native_id')
        );
        $this->assertFalse(
            method_exists(CommerceCatalogProductNameResolver::class, 'resolve_native_sku')
        );

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/currency_prices.php'
        );

        $this->assertStringContainsString(
            'CommerceCatalogProductNameResolver::resolve_native_id(',
            $contents
        );
    }
}
