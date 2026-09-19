<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e11_payment_architecture_certification_test extends advanced_testcase {
    public function test_certification_is_provider_agnostic_and_checks_core_contracts(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentArchitectureCertificationService.php'
        );

        $this->assertStringContainsString(
            'refund_contract_mismatch',
            $contents
        );
        $this->assertStringContainsString(
            'refund_history_without_refund',
            $contents
        );
        $this->assertStringContainsString(
            'provider_priority_tie',
            $contents
        );
        $this->assertStringContainsString(
            'Currency::is_known(',
            $contents
        );

        foreach ([
            'StripeCommercePaymentProvider',
            'AlfaCommercePaymentProvider',
            'PayPalCommercePaymentProvider',
        ] as $concreteproviderclass) {
            $this->assertStringNotContainsString(
                $concreteproviderclass,
                $contents
            );
        }
    }

    public function test_architecture_diagnostic_displays_certification_and_refund_history_contract(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );
        $inspector = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentArchitectureInspector.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentArchitectureCertificationService',
            $page
        );
        $this->assertStringContainsString(
            'commerce_payment_architecture_certification_title',
            $page
        );
        $this->assertStringContainsString(
            'refundhistorycontract',
            $inspector
        );
    }
}
