<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\currency\Currency;

final class commerce_796c22_currency_visual_consistency_test extends advanced_testcase {
    public function test_international_currency_markers_are_centralized(): void {
        $this->assertSame('🇯🇵', Currency::visual_marker('JPY'));
        $this->assertSame('🇹🇳', Currency::visual_marker('TND'));
        $this->assertSame('🇦🇪', Currency::visual_marker('AED'));
        $this->assertSame('🇨🇦', Currency::visual_marker('CAD'));
        $this->assertSame('🌍', Currency::visual_marker('XOF'));
    }

    public function test_native_commerce_currency_surfaces_use_the_central_marker(): void {
        $root = dirname(__DIR__, 3);
        $files = [
            '/admin/commerce/products/index.php',
            '/admin/commerce/products/view.php',
            '/admin/commerce/products/prices.php',
            '/admin/commerce/products/pricing.php',
            '/admin/commerce/products/preview.php',
            '/classes/crm/commerce/rendering/CommerceDashboardRenderer.php',
            '/classes/crm/commerce/statistics/CommerceGlobalStatisticsDashboardRenderer.php',
            '/classes/crm/commerce/statistics/CommerceProductStatisticsDashboardRenderer.php',
            '/classes/crm/commerce/statistics/CommerceStatisticsBreakdownRenderer.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents($root . $file);
            $this->assertStringContainsString('Currency::visual_marker', $source, $file);
        }
    }
}
