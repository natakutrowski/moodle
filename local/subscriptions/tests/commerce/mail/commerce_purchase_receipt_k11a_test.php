<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\presentation\CommerceMailPurchasePresentation;

final class commerce_purchase_receipt_k11a_test extends advanced_testcase {
    public function test_payment_presentation_has_provider_icon_and_translated_status(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $previous = force_current_language('fr');

        try {
            $presentation = CommerceMailPurchasePresentation::from_context(
                new CommerceMailContext([
                    'purchase' => [
                        'reference' => 'CFR-TEST',
                        'grossformatted' => '40,00 EUR',
                        'discountformatted' => '10,00 EUR',
                        'hasdiscount' => true,
                        'totalformatted' => '30,00 EUR',
                    ],
                    'items' => [],
                    'payment' => [
                        'provider' => 'stripe',
                        'providerlabel' => 'Stripe',
                        'transactionreference' => 'pi_test_123',
                        'status' => 'paid',
                    ],
                ])
            )->export();

            $this->assertTrue($presentation['payment']['hasprovidericon']);
            $this->assertStringContainsString(
                '/local/subscriptions/pix/email/stripe.png',
                $presentation['payment']['providericonurl']
            );
            $this->assertSame(
                get_string('commerce_mail_payment_status_paid_value', 'local_subscriptions'),
                $presentation['payment']['status']
            );
            $this->assertTrue($presentation['payment']['statussuccess']);
            $this->assertTrue($presentation['hasdiscount']);
        } finally {
            force_current_language($previous);
        }
    }

    public function test_receipt_template_uses_checkout_like_rows_and_monospace_transaction(): void {
        $root = dirname(__DIR__, 3);

        $receipt = (string)file_get_contents(
            $root . '/templates/commerce/mail/purchase_receipt.mustache'
        );
        $item = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/receipt_item.mustache'
        );

        $this->assertStringContainsString(
            'commerce/mail/components/receipt_item',
            $receipt
        );
        $this->assertStringContainsString(
            "font-family:'Courier New',Courier,monospace",
            $receipt
        );
        $this->assertStringContainsString(
            'payment.providericonurl',
            $receipt
        );
        $this->assertStringContainsString(
            'payment.statussuccess',
            $receipt
        );
        $this->assertStringContainsString(
            'text-decoration:line-through',
            $item
        );
        $this->assertStringContainsString(
            'width="64" height="64"',
            $item
        );
    }

    public function test_receipt_default_says_invoice_is_attached_now(): void {
        $root = dirname(__DIR__, 3);
        $defaults = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );
        $abstract = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );

        $this->assertStringContainsString(
            'Votre facture est jointe à cet e-mail au format PDF.',
            $defaults
        );
        $this->assertStringContainsString(
            'Счёт в формате PDF прикреплён к этому письму.',
            $defaults
        );
        $this->assertStringContainsString(
            '$legacyoutros',
            $abstract
        );
    }
}
