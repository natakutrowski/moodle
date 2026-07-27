<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\bundle\admin\CommerceBundleComponentInput;

final class commerce_bundle_component_editor_test extends advanced_testcase {
    public function test_build_ignores_empty_rows_and_orders_components(): void {
        $components = (new CommerceBundleComponentInput())->build('BUNDLE.ROOT', [
            ['sku' => 'DIGITAL.ONE', 'quantity' => 2, 'sortorder' => 20],
            ['sku' => '', 'quantity' => 1, 'sortorder' => 10],
            ['sku' => 'COURSE.ONE', 'quantity' => 1, 'sortorder' => 5],
        ]);

        $this->assertCount(2, $components);
        $this->assertSame('COURSE.ONE', $components[0]->get_child_product_sku());
        $this->assertSame('DIGITAL.ONE', $components[1]->get_child_product_sku());
        $this->assertSame(2, $components[1]->get_quantity());
    }

    public function test_build_rejects_duplicate_product(): void {
        $this->expectException(\coding_exception::class);
        (new CommerceBundleComponentInput())->build('BUNDLE.ROOT', [
            ['sku' => 'COURSE.ONE', 'quantity' => 1],
            ['sku' => 'COURSE.ONE', 'quantity' => 2],
        ]);
    }

    public function test_build_rejects_empty_definition(): void {
        $this->expectException(\coding_exception::class);
        (new CommerceBundleComponentInput())->build('BUNDLE.ROOT', [
            ['sku' => '', 'quantity' => 1],
        ]);
    }
}
