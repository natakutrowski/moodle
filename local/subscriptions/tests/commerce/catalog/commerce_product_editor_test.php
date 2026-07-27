<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductInput;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;

final class commerce_product_editor_test extends advanced_testcase {
    public function test_input_builds_new_product(): void {
        $input = new CommerceCatalogProductInput(
            'BUNDLE.A1',
            'bundle',
            'draft',
            'A1 Pack',
            'Complete pack'
        );

        $product = $input->to_product();

        $this->assertSame('BUNDLE.A1', $product->get_sku());
        $this->assertSame('bundle', $product->get_type());
        $this->assertSame('draft', $product->get_status());
        $this->assertSame('A1 Pack', $product->get_name());
    }

    public function test_input_preserves_persisted_identity(): void {
        $existing = new CommerceProduct(
            'BUNDLE.A1',
            'bundle',
            'draft',
            'Old name',
            '',
            [],
            42,
            null,
            null,
            100,
            200
        );
        $input = new CommerceCatalogProductInput('BUNDLE.A1', 'bundle', 'active', 'New name');
        $product = $input->to_product($existing);

        $this->assertSame(42, $product->get_id());
        $this->assertSame(100, $product->get_time_created());
        $this->assertSame('New name', $product->get_name());
    }
}
