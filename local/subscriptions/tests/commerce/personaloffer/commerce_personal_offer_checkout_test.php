<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\service\CommercePersonalOfferCheckoutPricingService;

final class commerce_personal_offer_checkout_test extends advanced_testcase {
    public function test_fixed_personal_price_is_resolved_server_side(): void {
        global $DB;
        $this->resetAfterTest(true);

        $products = new CommerceProductRepository($DB, new CommerceCatalogHydrator());
        $product = $products->save(new CommerceProduct(
            'PO-CHECKOUT-TEST',
            'digital_download',
            'active',
            'Personal Offer checkout test'
        ));

        $offers = new MoodleCommercePersonalOfferRepository($DB);
        $offer = $offers->save(new CommercePersonalOffer(
            null,
            str_repeat('a', 32),
            'checkout-test',
            null,
            (int)$product->get_id(),
            null,
            'buyer@example.com',
            CommercePersonalOffer::STATUS_ISSUED,
            CommercePersonalOfferTerms::fixed_price(['EUR' => 3000])
        ));

        $service = CommercePersonalOfferCheckoutPricingService::create($DB);
        $this->assertSame(3000, $service->resolve_unit_minor(
            $offer->get_offer_uuid(),
            $product->get_sku(),
            'EUR',
            3900
        ));
    }

    public function test_personal_offer_cannot_price_another_product(): void {
        global $DB;
        $this->resetAfterTest(true);

        $products = new CommerceProductRepository($DB, new CommerceCatalogHydrator());
        $product = $products->save(new CommerceProduct('PO-TARGET-A', 'digital_download', 'active', 'A'));
        $products->save(new CommerceProduct('PO-TARGET-B', 'digital_download', 'active', 'B'));

        $offer = (new MoodleCommercePersonalOfferRepository($DB))->save(new CommercePersonalOffer(
            null,
            str_repeat('b', 32),
            null,
            null,
            (int)$product->get_id(),
            null,
            'buyer@example.com',
            CommercePersonalOffer::STATUS_ISSUED,
            CommercePersonalOfferTerms::percentage_discount(2000)
        ));

        $this->expectException(\moodle_exception::class);
        CommercePersonalOfferCheckoutPricingService::create($DB)->resolve_unit_minor(
            $offer->get_offer_uuid(), 'PO-TARGET-B', 'EUR', 3900
        );
    }

    public function test_checkout_action_and_postpayment_contain_personal_offer_guards(): void {
        $root = dirname(__DIR__, 3);
        $checkout = (string)file_get_contents($root . '/commerce_checkout_action.php');
        $completer = (string)file_get_contents(
            $root . '/classes/commerce/fulfillment/native/checkout/CommerceNativePaidPurchaseCompleter.php'
        );

        $this->assertStringContainsString('assert_checkout_identity', $checkout);
        $this->assertStringContainsString('redeem_by_offer_uuid', $completer);
    }

    public function test_personal_offer_checkout_ux_locks_identity_and_exposes_currency_switch(): void {
        $root = dirname(__DIR__, 3);
        $checkout = (string)file_get_contents($root . '/commerce_checkout.php');
        $action = (string)file_get_contents($root . '/commerce_checkout_action.php');
        $template = (string)file_get_contents($root . '/templates/checkout/page.mustache');
        $currencyendpoint = (string)file_get_contents($root . '/offer_currency.php');
        $printtemplate = (string)file_get_contents($root . '/templates/cart/print.mustache');
        $presenter = (string)file_get_contents(
            $root . '/classes/commerce/cart/presentation/CommerceCartPresenter.php'
        );

        $this->assertStringContainsString('get_beneficiary_identity', $checkout);
        $this->assertStringContainsString('personalofferreservedfor', $checkout);
        $this->assertStringContainsString('personalofferhasmultiplecurrencies', $checkout);
        $this->assertStringContainsString('Identity attached to a Personal Offer is authoritative server-side', $action);
        $this->assertStringContainsString('{{personalofferbadge}}', $template);
        $this->assertStringContainsString('commerce-showroom-currency-card', $template);
        $this->assertStringContainsString('commerce-personal-offer-badge', $template);
        $this->assertStringContainsString('commerce-personal-offer-badge', $printtemplate);
        $this->assertStringContainsString("'ispersonaloffer' => \$ispersonaloffer", $presenter);
        $this->assertStringContainsString('name="email" value="{{personalofferemail}}"', $template);
        $this->assertStringContainsString('local_subscriptions_personal_offer_token', $currencyendpoint);
    }

}
