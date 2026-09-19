<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g7_payment_methods_certification_test extends advanced_testcase {
    public function test_certification_covers_g_scope(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/certification/'
            . 'CommercePaymentMethodsCertificationService.php'
        );

        foreach ([
            'CommercePaymentMethodCatalogue::keys()',
            'CommercePaymentMethod::APPLE_PAY',
            'CommercePaymentMethod::GOOGLE_PAY',
            'CommercePaymentMethod::LINK',
            'CommercePaymentMethod::KLARNA',
            'CommercePaymentMethodMarketEligibility::supports(',
            'allowed_methods()',
            'allowed_providers()',
        ] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

    public function test_certification_is_read_only(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/certification/'
            . 'CommercePaymentMethodsCertificationService.php'
        );

        foreach ([
            'create_payment(',
            'create_order(',
            'capture_order(',
            'refund_capture(',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $contents);
        }
    }
}
