<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontListFilter;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontPrice;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

final class commerce_795f1_storefront_read_model_test extends \advanced_testcase {
    public function test_storefront_value_objects_are_public_safe(): void {
        $price = new CommerceStorefrontPrice('eur', 490);
        $product = new CommerceStorefrontProduct(
            'digital.verbs',
            'Verbes français',
            'Un entraînement progressif.',
            '<p>Présentation complète.</p>',
            'digital_download',
            [$price],
            [],
            true
        );

        self::assertSame('EUR', $price->get_currency());
        self::assertSame(490, $price->get_amount_minor());
        self::assertSame('digital.verbs', $product->get_sku());
        self::assertTrue($product->is_quick_purchase_eligible());
        self::assertSame('Présentation complète.', strip_tags($product->get_description()));
    }

    public function test_storefront_filter_normalises_language_and_currency(): void {
        $filter = new CommerceStorefrontListFilter('FR-fr', 'eur', 'bundle', ' Mont Blanc ');

        self::assertSame('fr-fr', $filter->get_language());
        self::assertSame('EUR', $filter->get_currency());
        self::assertSame('bundle', $filter->get_type());
        self::assertSame('Mont Blanc', $filter->get_query());
    }
}
