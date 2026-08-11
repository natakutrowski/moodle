<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_email_ux_k10c_test extends advanced_testcase {
    public function test_personal_offer_uses_premium_cta_variant(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString("return 'premium';", $template);
        $this->assertStringContainsString('$buttonvariant === \'premium\'', $renderer);
        $this->assertStringContainsString('#d7b65a', $renderer);
        $this->assertStringContainsString('✦', $renderer);
    }

    public function test_validity_period_contains_start_and_end_without_timezone_shift(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );
        $mustache = (string)file_get_contents(
            $root . '/templates/commerce/mail/personal_offer.mustache'
        );

        $this->assertStringContainsString("'validfrom'=>", $service);
        $this->assertStringContainsString("'UTC'", $template);
        $this->assertStringContainsString('validfromformatted', $mustache);
        $this->assertStringContainsString('expiresformatted', $mustache);
        $this->assertStringContainsString('hasvalidityperiod', $mustache);
    }

    public function test_mail_shell_declares_email_safe_font_and_mobile_rules(): void {
        $root = dirname(__DIR__, 3);
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString('width=device-width,initial-scale=1', $renderer);
        $this->assertStringContainsString('font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif', $renderer);
        $this->assertStringContainsString('@media only screen and (max-width:600px)', $renderer);
        $this->assertStringContainsString('.ls-shell { width:100% !important', $renderer);
        $this->assertStringContainsString('ls-body', $renderer);
    }
}
