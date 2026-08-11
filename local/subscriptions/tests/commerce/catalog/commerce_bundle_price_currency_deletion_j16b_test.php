<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_bundle_price_currency_deletion_j16b_test extends \advanced_testcase {
    public function test_bundle_pricing_page_exposes_secure_currency_price_deletion(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/products/pricing.php');

        self::assertStringContainsString("\$action === 'deleteprice'", $page);
        self::assertStringContainsString('data_submitted() && confirm_sesskey()', $page);
        self::assertStringContainsString('$manager->delete_price($sku, $priceid);', $page);
        self::assertStringContainsString("'method' => 'post'", $page);
        self::assertStringContainsString('commerce_price_currency_delete_confirm', $page);
    }
}
