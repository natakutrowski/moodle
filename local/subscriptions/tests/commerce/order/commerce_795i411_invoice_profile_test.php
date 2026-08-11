<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\order\invoice\CommerceInvoiceProfileResolver;

/** Tests the deterministic EUR/RUB invoice issuer selection. */
final class commerce_795i411_invoice_profile_test extends \advanced_testcase {
    public function test_eur_and_rub_profiles_are_selected_by_currency(): void {
        $this->resetAfterTest();
        set_config('invoice_eur_name', 'CampusFR EUR', 'local_subscriptions');
        set_config('invoice_rub_name', 'CampusFR RUB', 'local_subscriptions');

        $resolver = new CommerceInvoiceProfileResolver();
        $eur = $resolver->resolve('EUR', 'stripe');
        $rub = $resolver->resolve('RUB', 'alfa');

        $this->assertSame('eur', $eur['key']);
        $this->assertSame('CampusFR EUR', $eur['name']);
        $this->assertFalse($eur['mismatch']);
        $this->assertSame('rub', $rub['key']);
        $this->assertSame('CampusFR RUB', $rub['name']);
        $this->assertFalse($rub['mismatch']);
    }

    public function test_currency_provider_mismatch_is_reported(): void {
        $this->resetAfterTest();
        $profile = (new CommerceInvoiceProfileResolver())->resolve('EUR', 'alfa');
        $this->assertSame('eur', $profile['key']);
        $this->assertTrue($profile['mismatch']);
    }
}
