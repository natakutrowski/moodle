<?php

declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\invoice\CommerceInvoiceProfileResolver;
use local_subscriptions\commerce\showroom\legal\CommerceShowroomLegalFooterPresenter;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolver;
use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolutionContext;

/** P2F: only footer presentation follows invoice profile; merchant policy unchanged. */
final class commerce_798x49_footer_glass_don10_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_native_footer_uses_configured_currency_invoice_fields(): void {
        set_config('invoice_eur_name', 'TEST EUR ENTITY', 'local_subscriptions');
        set_config('invoice_rub_name', 'TEST RUB ENTITY', 'local_subscriptions');
        $resolver = new CommerceInvoiceProfileResolver();
        $presenter = new CommerceShowroomLegalFooterPresenter();
        $urls = ['privacy' => '/local/subscriptions/pages/policy_fr.php',
                 'terms' => '/local/subscriptions/pages/terms_fr.php',
                 'offer' => '/local/subscriptions/pages/terms_fr.php'];
        foreach (['EUR'=>'TEST EUR ENTITY', 'RUB'=>'TEST RUB ENTITY'] as $currency=>$name) {
            $p = $resolver->resolve($currency, null);
            $footer = $presenter->present(['fields'=>$p,'urls'=>$urls]);
            self::assertSame($name, $footer['finallegalname']);
            self::assertTrue($footer['finallegalshowname']);
            self::assertSame($urls['offer'], $footer['finallegalofferurl']);
        }
    }

    public function test_checkout_merchant_still_selected_by_market_not_currency(): void {
        $resolver = new CommerceMerchantResolver();
        foreach (['FR'=>'fr_main','RU'=>'ru_main','BY'=>'ru_main','ZZ'=>'fr_main'] as $market=>$entity) {
            foreach (['EUR','RUB'] as $currency) {
                $resolution = $resolver->resolve(new CommerceMerchantResolutionContext($market, $currency));
                self::assertSame($entity, $resolution->get_entity_key());
            }
        }
    }

    public function test_no_checkout_or_currency_policy_changed_by_delta(): void {
        $root=dirname(__DIR__,3);
        $native=file_get_contents($root.'/showroom.php');
        self::assertStringContainsString("'fields' => \$finallegalprofilefields", $native);
        self::assertStringContainsString('CommerceInvoiceProfileResolver', $native);
        self::assertStringContainsString('CommerceShowroomLegalFooterPresenter', $native);
    }
}
