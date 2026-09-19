<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f4_paypal_webhook_security_test extends advanced_testcase {
    public function test_webhook_signature_verifier_uses_official_verification_api(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/'
            . 'PayPalWebhookSignatureVerifier.php'
        );

        foreach ([
            '/v1/notifications/verify-webhook-signature',
            'paypal-auth-algo',
            'paypal-cert-url',
            'paypal-transmission-id',
            'paypal-transmission-sig',
            'paypal-transmission-time',
            "'webhook_id' => \$webhookid",
            "'verification_status'",
            "'SUCCESS'",
            "require_once(",
            "'/filelib.php'",
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_webhook_endpoint_never_processes_unverified_payload_directly(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/webhook/paypal.php'
        );

        $verifyposition = strpos(
            $contents,
            ')->verify('
        );
        $handleposition = strpos(
            $contents,
            '->handle($verified)'
        );

        $this->assertNotFalse($verifyposition);
        $this->assertNotFalse($handleposition);
        $this->assertLessThan(
            $handleposition,
            $verifyposition
        );
    }
}
