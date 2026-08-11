<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_payment_state_cta_icons_test extends advanced_testcase {

    public function test_payment_state_templates_use_receipt_cta_icon(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            'CommercePaymentFailedTemplate.php',
            'CommercePaymentCancelledTemplate.php',
            'CommercePaymentPendingTemplate.php',
        ] as $filename) {
            $source = (string)file_get_contents(
                $root . '/classes/commerce/mail/template/' . $filename
            );
            $this->assertStringContainsString("return 'receipt';", $source);
        }
    }

    public function test_mail_renderer_supports_email_safe_receipt_icon(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString("buttonicon === 'receipt'", $source);
        $this->assertStringContainsString('receipt-white.png', $source);
        $this->assertFileExists($root . '/pix/email/receipt-white.png');
    }
}
