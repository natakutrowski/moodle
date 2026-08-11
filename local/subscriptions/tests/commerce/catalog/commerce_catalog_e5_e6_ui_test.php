<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogListFilter;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogPrice;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogProductSummary;
use local_subscriptions\commerce\catalog\status\CommerceCatalogStatusSnapshot;

final class commerce_catalog_e5_e6_ui_test extends advanced_testcase {
    public function test_filter_combines_catalogue_dimensions(): void {
        $product = new CommerceCatalogProductSummary(
            7,
            'COURSE.A1',
            'Cours A1 complet',
            'Programme de français',
            'course_access',
            'native',
            new CommerceCatalogStatusSnapshot('published', 'visible', 'on_sale', 'valid'),
            [new CommerceCatalogPrice('EUR', 25000, 'stripe', true, 'native')]
        );

        $this->assertTrue((new CommerceCatalogListFilter('A1', 'course_access', 'published', 'visible', 'on_sale', 'valid', 'EUR', 'native'))->matches($product));
        $this->assertFalse((new CommerceCatalogListFilter('', '', '', '', '', '', 'RUB'))->matches($product));
        $this->assertFalse((new CommerceCatalogListFilter('A2'))->matches($product));
    }

    public function test_unified_pages_use_origin_and_identifier(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/products/index.php');
        $view = file_get_contents($root . '/admin/commerce/products/view.php');

        $this->assertStringContainsString('CommerceCatalogReadRepository', $index);
        $this->assertStringContainsString('CommerceCatalogLinkGenerator::view_url($product)', $index);
        $this->assertStringContainsString('find_by_origin_and_id', $view);
        $this->assertStringContainsString("\$product->get_origin() === 'native'", $view);
    }
}
