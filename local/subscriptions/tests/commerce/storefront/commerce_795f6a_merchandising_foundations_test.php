<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\merchandising\CommerceStorefrontMerchandisingResolver;
use local_subscriptions\commerce\storefront\merchandising\CommerceStorefrontPromotionResolver;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

final class commerce_795f6a_merchandising_foundations_test extends \advanced_testcase {
    public function test_merchandising_metadata_is_normalised_safely(): void {
        $resolver = new CommerceStorefrontMerchandisingResolver();
        $result = $resolver->resolve([
            'storefront' => [
                'merchandising' => [
                    'featured' => true,
                    'displayorder' => -8,
                    'badges' => ['new', 'unknown', 'new', 'premium'],
                    'promotions' => [
                        'eur' => ['compareamountminor' => 19900, 'start' => 100, 'end' => 200],
                        'INVALID' => ['compareamountminor' => 500],
                    ],
                ],
            ],
        ]);

        self::assertTrue($result->is_featured());
        self::assertSame(0, $result->get_display_order());
        self::assertSame(['new', 'premium'], $result->get_badges());
        self::assertSame(19900, $result->get_promotion('EUR')['compareamountminor']);
        self::assertNull($result->get_promotion('INVALID'));
    }

    public function test_promotion_is_active_only_inside_window_and_above_sale_price(): void {
        $merchandising = (new CommerceStorefrontMerchandisingResolver())->resolve([
            'storefront' => [
                'merchandising' => [
                    'promotions' => [
                        'EUR' => ['compareamountminor' => 20000, 'start' => 100, 'end' => 300],
                    ],
                ],
            ],
        ]);
        $resolver = new CommerceStorefrontPromotionResolver();

        self::assertNull($resolver->resolve($merchandising, 'EUR', 15000, 99));
        self::assertNull($resolver->resolve($merchandising, 'EUR', 15000, 301));
        self::assertNull($resolver->resolve($merchandising, 'EUR', 20000, 200));

        $promotion = $resolver->resolve($merchandising, 'EUR', 15000, 200);
        self::assertSame(20000, $promotion['compareamountminor']);
        self::assertSame(25, $promotion['discountpercentage']);
        self::assertSame(300, $promotion['end']);
    }

    public function test_storefront_read_models_expose_merchandising_without_changing_sale_price(): void {
        $price = new CommerceStorefrontPrice('EUR', 14900, 19900, 25, 2000000000);
        $product = new CommerceStorefrontProduct(
            'COURSE-A1',
            'A1',
            '',
            '',
            'course_access',
            [$price],
            [],
            true,
            null,
            [],
            [],
            true,
            10,
            ['bestseller']
        );

        self::assertSame(14900, $price->get_amount_minor());
        self::assertTrue($price->has_active_promotion());
        self::assertTrue($product->is_featured());
        self::assertSame(10, $product->get_display_order());
        self::assertSame(['bestseller'], $product->get_badges());
    }
}
