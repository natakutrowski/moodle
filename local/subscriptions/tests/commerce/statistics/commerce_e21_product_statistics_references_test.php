<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_e21_product_statistics_references_test extends \advanced_testcase {
    public function test_product_page_uses_native_and_legacy_references(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents($root . '/admin/commerce/products/view.php');
        $repository = file_get_contents($root . '/classes/commerce/statistics/CommerceStatisticsRepository.php');
        $this->assertIsString($view);
        $this->assertIsString($repository);
        $this->assertStringContainsString("'subscription-plan:'", $view);
        $this->assertStringContainsString("'digital-product:'", $view);
        $this->assertStringContainsString('product_statistics_for_references', $repository);
        $this->assertStringContainsString('product_revenue_series_for_references', $repository);
    }
}
