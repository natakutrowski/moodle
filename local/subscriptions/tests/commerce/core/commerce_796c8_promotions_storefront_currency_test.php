<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionValidator;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;

/** Tests 7.96C8 promotion/storefront currency generalization and product statistics regression. */
final class commerce_796c8_promotions_storefront_currency_test extends advanced_testcase {
    public function test_showroom_resolver_accepts_enabled_non_legacy_currencies(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,JPY,TND', 'local_subscriptions');

        self::assertSame('JPY', CommerceShowroomCurrencyResolver::resolve(['EUR', 'JPY', 'TND'], 'JPY'));
        self::assertSame('TND', CommerceShowroomCurrencyResolver::resolve(['EUR', 'JPY', 'TND'], 'TND'));
    }

    public function test_fixed_promotion_requires_an_explicit_currency(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,JPY,TND', 'local_subscriptions');

        $validator = new CommercePromotionValidator();
        $repository = new MoodleCommercePromotionRepository();
        $base = [
            'name' => 'Test',
            'code' => 'C8FIXED',
            'automatic' => false,
            'discounttype' => CommercePromotion::TYPE_FIXED,
            'discountvalue' => 449,
            'minimumcartminor' => 0,
            'currency' => '',
            'startsat' => null,
            'endsat' => null,
        ];

        $errors = $validator->validate($base, $repository);
        self::assertArrayHasKey('currency', $errors);

        $base['currency'] = 'JPY';
        $errors = $validator->validate($base, $repository);
        self::assertArrayNotHasKey('currency', $errors);
    }

    public function test_monetary_minimum_requires_currency_even_for_percentage_promotion(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,JPY,TND', 'local_subscriptions');

        $errors = (new CommercePromotionValidator())->validate([
            'name' => 'Test percent',
            'code' => 'C8PERCENT',
            'automatic' => false,
            'discounttype' => CommercePromotion::TYPE_PERCENTAGE,
            'discountvalue' => 2000,
            'minimumcartminor' => 1000,
            'currency' => '',
            'startsat' => null,
            'endsat' => null,
        ], new MoodleCommercePromotionRepository());

        self::assertArrayHasKey('currency', $errors);
    }

    public function test_product_statistics_and_admin_surfaces_use_currency_core(): void {
        $root = dirname(__DIR__, 3);

        $productview = file_get_contents($root . '/admin/commerce/products/view.php');
        self::assertIsString($productview);
        self::assertStringContainsString('$statisticsallowedcurrencies', $productview);
        self::assertStringNotContainsString('!isset($availablecurrencies[$statisticscurrency])', $productview);

        $preview = file_get_contents($root . '/admin/commerce/products/preview.php');
        self::assertIsString($preview);
        self::assertStringContainsString('CurrencyFormatter::format_minor_code', $preview);

        $promotionedit = file_get_contents($root . '/admin/commerce/promotions/edit.php');
        self::assertIsString($promotionedit);
        self::assertStringContainsString('CommerceMoney::from_major_for_currency', $promotionedit);

        $storefront = file_get_contents($root . '/storefront_product.php');
        self::assertIsString($storefront);
        self::assertStringContainsString('CommerceCurrencyRegistry', $storefront);
        self::assertStringNotContainsString("\$availablecurrencies = ['EUR', 'RUB']", $storefront);
    }
}
