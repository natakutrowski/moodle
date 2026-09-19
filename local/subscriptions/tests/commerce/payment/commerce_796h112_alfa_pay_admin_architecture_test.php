<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\availability\CommercePaymentMethodMarketEligibility;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;

final class commerce_796h112_alfa_pay_admin_architecture_test extends \advanced_testcase {
    public function test_alfa_pay_is_a_first_class_commerce_payment_method(): void {
        $this->assertContains(CommercePaymentMethod::ALFA_PAY, CommercePaymentMethod::KNOWN);
        $this->assertContains(CommercePaymentMethod::ALFA_PAY, CommercePaymentMethodCatalogue::keys());
        $this->assertSame('alfa_pay', CommercePaymentMethod::ALFA_PAY);
    }

    public function test_alfa_pay_market_scope_is_rub_only(): void {
        $this->assertTrue(CommercePaymentMethodMarketEligibility::supports('alfa_pay', 'RUB', 'RU'));
        $this->assertTrue(CommercePaymentMethodMarketEligibility::supports('alfa_pay', 'RUB', 'FR'));
        $this->assertFalse(CommercePaymentMethodMarketEligibility::supports('alfa_pay', 'EUR', 'FR'));
        $this->assertTrue(CommercePaymentMethodMarketEligibility::is_market_dependent('alfa_pay'));
    }

    public function test_payment_configuration_automatically_lists_catalogue_methods(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php');
        $this->assertStringContainsString('CommercePaymentMethodCatalogue::keys()', $source);
        $this->assertStringContainsString("'commerce_presented_payment_methods'", $source);
    }
}
