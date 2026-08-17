<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_products_n817_test extends advanced_testcase {
    public function test_builder_keeps_diffusion_settings_out_of_visible_editor(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/storefront_builder.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("'type' => 'hidden', 'name' => 'storefront_featured'", $source);
        $this->assertStringContainsString("'type' => 'hidden', 'name' => 'storefront_group'", $source);
        $this->assertStringNotContainsString("get_string('commerce_storefront_merchandising_title'", $source);
        $this->assertStringNotContainsString("get_string('commerce_storefront_experience_title'", $source);
    }

    public function test_builder_hides_redundant_section_type_and_technical_fields(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/storefront_builder.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("'name' => 'section_type_' . \$index", $source);
        $this->assertStringContainsString("'name' => 'section_id_' . \$index", $source);
        $this->assertStringContainsString("'name' => 'section_style_' . \$index", $source);
        $this->assertStringContainsString('commerce_storefront_n817_block_preview', $source);
    }

    public function test_builder_limits_generic_items_to_card_like_blocks(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/storefront_builder.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("in_array(\$row['type'], ['features', 'faq'], true)", $source);
        $this->assertStringContainsString("in_array(\$row['type'], ['hero', 'image_text'], true)", $source);
    }
}
