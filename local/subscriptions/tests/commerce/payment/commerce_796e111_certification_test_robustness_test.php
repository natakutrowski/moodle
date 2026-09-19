<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e111_certification_test_robustness_test extends advanced_testcase {
    public function test_certification_service_does_not_reference_concrete_provider_classes(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentArchitectureCertificationService.php'
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

        $this->assertStringContainsString(
            'CommercePaymentProviderRegistry',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceRefundCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceRefundHistoryCapablePaymentProvider',
            $contents
        );
    }
}
