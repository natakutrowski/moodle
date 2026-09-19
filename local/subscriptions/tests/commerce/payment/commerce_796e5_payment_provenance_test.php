<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProvenance;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e5_payment_provenance_test extends advanced_testcase {
    public function test_provenance_keeps_provider_and_payment_method_separate(): void {
        $request = new CommercePaymentRequest(
            'PAY.E5',
            new CommercePaymentCustomer(
                null,
                'e5@example.test',
                'E5'
            ),
            [
                new CommercePaymentLine(
                    'SKU.E5',
                    'E5',
                    1,
                    3000,
                    'EUR'
                ),
            ],
            'EUR',
            3000,
            null,
            null,
            null,
            [
                'payment_country' => 'FR',
            ],
            null,
            CommercePaymentMethod::CARD
        );

        $provenance = CommercePaymentProvenance::from_request(
            $request,
            'stripe'
        )->to_array();

        $this->assertSame('stripe', $provenance['provider']);
        $this->assertSame('card', $provenance['paymentmethod']);
        $this->assertSame('EUR', $provenance['currency']);
        $this->assertSame('FR', $provenance['country']);
    }

    public function test_orchestrator_exposes_provenance_in_initialize_and_simulate_metadata(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/orchestration/'
            . 'CommercePaymentOrchestrator.php'
        );

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $contents,
                'CommercePaymentProvenance::from_request('
            )
        );
        $this->assertStringContainsString(
            "'paymentmethod'",
            file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/orchestration/'
                . 'CommercePaymentProvenance.php'
            )
        );
    }
}
