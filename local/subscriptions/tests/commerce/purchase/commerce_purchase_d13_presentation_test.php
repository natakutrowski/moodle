<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_purchase_d13_presentation_test extends advanced_testcase {
    public function test_statuses_and_fulfillment_types_are_localised(): void {
        $this->resetAfterTest();

        $manager = get_string_manager();
        $this->assertTrue($manager->string_exists('commerce_purchase_payment_status_paid', 'local_subscriptions'));
        $this->assertTrue($manager->string_exists('commerce_purchase_fulfillment_status_failed', 'local_subscriptions'));
        $this->assertTrue($manager->string_exists(
            'commerce_purchase_fulfillment_type_subscription_enrolment',
            'local_subscriptions'
        ));
        $this->assertTrue($manager->string_exists(
            'commerce_purchase_fulfillment_type_digital_download',
            'local_subscriptions'
        ));
        $this->assertSame(
            get_string('commerce_purchase_payment_status_paid', 'local_subscriptions'),
            CommercePurchasePresentation::technical_status_label('payment', 'paid')
        );
        $this->assertSame(
            get_string('commerce_purchase_fulfillment_type_subscription_enrolment', 'local_subscriptions'),
            CommercePurchasePresentation::fulfillment_label('subscription_enrolment')
        );
    }

    public function test_failed_fulfillment_summary_can_be_retried(): void {
        $summary = new CommercePurchaseSummary(
            42,
            'uuid',
            'PUR-42',
            'subscription',
            new CommercePurchaseCustomer(7, 'client@example.test', 'Jean', 'Dupont'),
            ['Cours A1'],
            'EUR',
            12000,
            'to_fulfill',
            'paid',
            'failed',
            'stripe',
            'native',
            1700000000
        );

        $this->assertTrue((new CommercePurchaseActionPolicy())->can_retry_summary($summary));
    }
}
