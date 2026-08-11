<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

/** Regression coverage for J15H.1E.1 storefront product identity. */
final class commerce_showroom_visual_optimisation_j15h1e1_test extends \advanced_testcase {
    public function test_storefront_product_exposes_optional_catalogue_id(): void {
        $product = new CommerceStorefrontProduct(
            'TEST-SKU', 'Test', '', '', 'course', [], [], false,
            null, [], [], false, 1000, [], 'courses', [], [], false,
            null, [], 42
        );

        self::assertSame(42, $product->get_id());
        self::assertSame(42, $product->to_array()['id']);
    }
}
