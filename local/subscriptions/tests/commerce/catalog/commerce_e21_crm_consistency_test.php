<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_e21_crm_consistency_test extends \advanced_testcase {
    public function test_native_catalogue_is_the_only_product_navigation(): void {
        $root = dirname(__DIR__, 3);
        $navigation = file_get_contents($root . '/classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php');
        $this->assertIsString($navigation);
        $this->assertStringNotContainsString('subscription_config::digital_products_admin_page()', $navigation);
    }

    public function test_purchase_list_links_products_and_shows_one_status(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/purchases/index.php');
        $this->assertIsString($index);
        $this->assertStringContainsString('find_by_purchase_reference', $index);
        $this->assertStringContainsString('CommerceCatalogLinkGenerator::view_url', $index);
        $this->assertStringNotContainsString("technical_status_badge('payment'", $index);
        $this->assertStringNotContainsString("technical_status_badge('fulfillment'", $index);
    }

    public function test_digital_files_are_downloadable_without_duplicate_cover(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents($root . '/admin/commerce/products/view.php');
        $download = file_get_contents($root . '/admin/commerce/products/download.php');
        $this->assertIsString($view);
        $this->assertIsString($download);
        $this->assertStringContainsString("products/download.php", $view);
        $this->assertStringNotContainsString('$digital->coverimage', $view);
        $this->assertStringContainsString('send_file(', $download);
    }
}
