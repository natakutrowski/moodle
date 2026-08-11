<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontUrlResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\url\UrlFactory;

/**
 * @covers \local_subscriptions\commerce\storefront\presentation\CommerceStorefrontUrlResolver
 */
final class commerce_storefront_owned_bundle_navigation_test extends advanced_testcase {
    public function test_owned_bundle_opens_unified_my_campus(): void {
        $product = new CommerceStorefrontProduct(
            'BUNDLE.TEST',
            'Bundle test',
            '',
            '',
            'bundle',
            [],
            [],
            false,
            null,
            [],
            []
        );

        $url = CommerceStorefrontUrlResolver::owned_access($product)->out(false);

        self::assertSame(
            UrlFactory::my_campus()->out(false),
            $url
        );
        self::assertNotSame(
            UrlFactory::my_purchases()->out(false),
            $url
        );
    }
}
