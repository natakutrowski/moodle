<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\bundle\preview\CommerceBundlePreviewItem;
use local_subscriptions\commerce\bundle\preview\CommerceBundlePreviewResult;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;

final class commerce_bundle_preview_test extends advanced_testcase {
    public function test_preview_result_summarises_items(): void {
        $bundle = new CommerceProduct('BUNDLE.TEST', 'bundle', CommerceProductStatus::INACTIVE, 'Bundle');
        $product = new CommerceProduct('COURSE.TEST', 'course_access', CommerceProductStatus::ACTIVE, 'Course');
        $item = new CommerceBundlePreviewItem($product, 3, [['BUNDLE.TEST', 'COURSE.TEST']], [], []);
        $result = new CommerceBundlePreviewResult($bundle, [$item], 1, 1);

        $this->assertSame(1, $result->get_product_count());
        $this->assertSame(3, $result->get_total_quantity());
        $this->assertSame(0, $result->get_entitlement_count());
        $this->assertSame(1, $result->get_maximum_depth());
    }

    public function test_preview_item_rejects_zero_quantity(): void {
        $product = new CommerceProduct('COURSE.TEST', 'course_access', CommerceProductStatus::ACTIVE, 'Course');
        $this->expectException(\coding_exception::class);
        new CommerceBundlePreviewItem($product, 0, [], [], []);
    }
}
