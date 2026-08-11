<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_trial_welcome_k125a1_test extends advanced_testcase {
    public function test_renderer_outputs_after_button_html(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/mail/MailRenderer.php'
        );

        $this->assertStringContainsString(
            "'." . '$btn' . ".'",
            $source
        );
        $this->assertStringContainsString(
            "'." . '$afterbuttonhtml' . ".'",
            $source
        );
    }

    public function test_trial_template_provides_photo_after_cta(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommerceTrialWelcomeTemplate.php'
        );

        $this->assertStringContainsString(
            'primary_action_after_html',
            $source
        );
        $this->assertStringContainsString(
            'trial-welcome.jpg',
            $source
        );
    }
}
