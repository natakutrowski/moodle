<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_trial_welcome_k125a_test extends advanced_testcase {
    public function test_trial_hero_is_rendered_after_primary_cta(): void {
        $root = dirname(__DIR__, 3);

        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');
        $abstract = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );
        $trial = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommerceTrialWelcomeTemplate.php'
        );

        $this->assertStringContainsString('afterbuttonhtml', $renderer);
        $this->assertStringContainsString('primary_action_after_html', $abstract);
        $this->assertStringContainsString('trial-welcome.jpg', $trial);
    }

    public function test_trial_support_address_is_a_mailto_link(): void {
        $root = dirname(__DIR__, 3);
        $defaults = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );

        $this->assertStringContainsString('mailto:{support_email}', $defaults);
    }
}
