<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g8_provider_operations_status_test extends advanced_testcase {
    public function test_provider_status_service_covers_stripe_alfa_and_paypal(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentProviderOperationalStatusService.php'
        );

        foreach ([
            'function stripe()',
            'function alfa()',
            'function paypal()',
            'StripeConfiguration::active_profile()',
            'PayPalGatewayConfiguration',
            'alfa_env',
            'is_provider_allowed(',
        ] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

    public function test_status_service_never_exposes_secret_values(): void {
        global $CFG;

        $dto = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentProviderOperationalStatus.php'
        );

        $this->assertStringNotContainsString(
            'secret_key',
            $dto
        );
        $this->assertStringNotContainsString(
            'client_secret',
            $dto
        );
        $this->assertStringNotContainsString(
            'password',
            $dto
        );
    }
}
