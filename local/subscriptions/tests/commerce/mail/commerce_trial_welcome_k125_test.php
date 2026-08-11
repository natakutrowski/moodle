<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_trial_welcome_k125_test extends advanced_testcase {
    public function test_trial_template_has_no_telegram_and_uses_external_cta(): void {
        $root = dirname(__DIR__, 3);

        $mustache = (string)file_get_contents(
            $root . '/templates/commerce/mail/trial_welcome.mustache'
        );
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommerceTrialWelcomeTemplate.php'
        );
        $service = (string)file_get_contents(
            $root . '/classes/commerce/mail/service/CommerceTrialWelcomeMailService.php'
        );

        $this->assertStringNotContainsString('welcome_telegram', $mustache);
        $this->assertStringContainsString("return 'external';", $template);
        $this->assertStringContainsString("new \\moodle_url('/mes-cours')", $service);
    }

    public function test_trial_editorial_copy_keeps_legacy_onboarding_content(): void {
        $root = dirname(__DIR__, 3);
        $defaults = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );

        $this->assertStringContainsString('20 минут в день', $defaults);
        $this->assertStringContainsString('практика с настоящим французом', $defaults);
        $this->assertStringContainsString('20 minutes par jour', $defaults);
        $this->assertStringContainsString('support_email', $defaults);
    }
}
