<?php

declare(strict_types=1);

namespace local_subscriptions;

/**
 * Contract tests for unified Storefront media rendering.
 *
 * @coversNothing
 */
final class commerce_storefront_media_unification_j889_test extends \advanced_testcase {
    public function test_presenter_builds_direct_image_and_h5p_html(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("'imagehtml' => ", $source);
        $this->assertStringContainsString("'h5phtml' => ", $source);
        $this->assertStringContainsString("get_slot_url(\$itemid, 'image')", $source);
        $this->assertStringContainsString("get_slot_url(\n            \$itemid,\n            'h5p'", $source);
    }

    public function test_template_renders_unescaped_media_html(): void {
        $source = file_get_contents(__DIR__ . '/../../../templates/storefront/product_section.mustache');
        $this->assertIsString($source);
        $this->assertStringContainsString('{{{imagehtml}}}', $source);
        $this->assertStringContainsString('{{{h5phtml}}}', $source);
    }

    public function test_sections_remain_visible_without_javascript(): void {
        $source = file_get_contents(__DIR__ . '/../../../styles/storefront.css');
        $this->assertIsString($source);
        $this->assertStringContainsString('[data-premium-ready][data-premium-animation="fade"]', $source);
        $this->assertStringNotContainsString('[data-premium-animation]:not(.is-premium-visible) { opacity:0; }', $source);
    }
}
