<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_placeholders_k11b2_test extends advanced_testcase {
    public function test_receipt_and_access_mail_use_placeholders_without_cover(): void {
        $root = dirname(__DIR__, 3);
        $receipt = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/receipt_item.mustache'
        );
        $access = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );
        $presentation = (string)file_get_contents(
            $root . '/classes/commerce/mail/presentation/CommerceMailPurchasePresentation.php'
        );

        $this->assertStringContainsString('{{^hascover}}', $receipt);
        $this->assertStringContainsString('{{placeholderurl}}', $receipt);
        $this->assertStringContainsString('{{^hascover}}', $access);
        $this->assertStringContainsString('{{placeholderurl}}', $access);

        foreach (['course', 'digital', 'bundle', 'product'] as $type) {
            $this->assertStringContainsString(
                "placeholder-'.\$placeholdertype.'.png",
                str_replace(["\n", " "], '', $presentation)
            );
            break;
        }
    }

    public function test_view_order_button_is_secondary_and_compact(): void {
        $root = dirname(__DIR__, 3);
        $receipt = (string)file_get_contents(
            $root . '/templates/commerce/mail/purchase_receipt.mustache'
        );

        $this->assertStringContainsString('padding:7px 10px', $receipt);
        $this->assertStringContainsString('font-size:12px', $receipt);
        $this->assertStringContainsString('width="12" height="12"', $receipt);
    }
}
