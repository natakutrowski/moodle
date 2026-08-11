<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_e21_fix_product_performance_test extends \advanced_testcase {
    public function test_purchase_links_use_canonical_catalogue_identity(): void {
        $root = dirname(__DIR__, 3);
        $index = (string)file_get_contents($root . '/admin/commerce/purchases/index.php');
        $repository = (string)file_get_contents($root . '/classes/commerce/catalog/readmodel/CommerceCatalogReadRepository.php');
        $this->assertStringContainsString('find_by_purchase_reference', $index);
        $this->assertStringContainsString('CommerceCatalogLinkGenerator::view_url', $index);
        $this->assertStringContainsString("'subscription_digital_product'", $repository);
    }

    public function test_product_statistics_support_slug_currency_and_period_filters(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents($root . '/admin/commerce/products/view.php');
        $repository = (string)file_get_contents($root . '/classes/commerce/statistics/CommerceStatisticsRepository.php');
        $this->assertStringContainsString("'digital-product:' . trim", $view);
        $this->assertStringContainsString("optional_param('statscurrency'", $view);
        $this->assertStringContainsString("optional_param('statsperiod'", $view);
        $this->assertStringContainsString('pi.currency = :productcurrency', $repository);
        $this->assertStringContainsString("CommerceStatisticsPeriod::custom(0, time() + 1)", $view);
    }

    public function test_legacy_digital_cover_has_priority(): void {
        $root = dirname(__DIR__, 3);
        $view = (string)file_get_contents($root . '/admin/commerce/products/view.php');
        $legacypos = strpos($view, '$legacydigital && !empty($legacydigital->coverimage)');
        $nativepos = strpos($view, '$coverurl === null && $product->get_origin()');
        $this->assertNotFalse($legacypos);
        $this->assertNotFalse($nativepos);
        $this->assertLessThan($nativepos, $legacypos);
    }
}
