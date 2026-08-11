<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_purchase_access_mail_k10g1_test extends advanced_testcase {
    public function test_access_buttons_use_email_safe_campus_icons(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString('{{course_icon_url}}', $template);
        $this->assertStringContainsString('{{download_icon_url}}', $template);
        $this->assertStringContainsString('{{mobile_icon_url}}', $template);
        $this->assertStringNotContainsString('<span style="font-size:12px;">▶</span>', $template);
        $this->assertStringNotContainsString('<span style="font-size:13px;">▣</span>', $template);
        $this->assertStringNotContainsString('<span style="font-size:15px;">▯</span>', $template);
    }

    public function test_mobile_download_is_outline_and_classic_is_filled(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/templates/commerce/mail/components/access_item.mustache'
        );

        $this->assertStringContainsString('background:#f72585;color:#ffffff', $template);
        $this->assertStringContainsString('background:#ffffff;color:#f72585', $template);
        $this->assertStringContainsString('{{download_desktop_label}}', $template);
        $this->assertStringContainsString('{{download_mobile_label}}', $template);
    }
}
