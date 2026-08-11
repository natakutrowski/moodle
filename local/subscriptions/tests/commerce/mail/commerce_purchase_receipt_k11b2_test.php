<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_purchase_receipt_k11b2_test extends advanced_testcase {
    public function test_purchase_receipt_primary_action_is_campus_with_external_icon(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePurchaseReceiptTemplate.php'
        );
        $renderer = (string)file_get_contents(
            $root . '/classes/mail/MailRenderer.php'
        );

        $this->assertStringContainsString('commerce_mail_access_my_campus', $template);
        $this->assertStringContainsString("return 'external';", $template);
        $this->assertStringContainsString("buttonicon === 'external'", $renderer);
        $this->assertStringContainsString('external-white.png', $renderer);
    }

    public function test_view_order_is_inside_payment_block_only(): void {
        $root = dirname(__DIR__, 3);
        $receipt = (string)file_get_contents(
            $root . '/templates/commerce/mail/purchase_receipt.mustache'
        );

        $this->assertSame(1, substr_count($receipt, '{{receipt_view_order_label}}'));
        $this->assertSame(1, substr_count($receipt, 'href="{{links.order}}"'));
        $this->assertStringContainsString('{{#links.hasorder}}', $receipt);
        $this->assertStringNotContainsString(
            '{{#links.hascampus}}{{#links.hasorder}}',
            $receipt
        );
    }
}
