<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_welcome_k126_test extends advanced_testcase {
    public function test_welcome_has_expected_ux_blocks(): void {
        $root = dirname(__DIR__, 3);
        $tpl = (string)file_get_contents($root . '/templates/commerce/mail/account_activation.mustache');

        $this->assertStringContainsString('welcome_login_email_label', $tpl);
        $this->assertStringContainsString('welcome_activation_explanation', $tpl);
        $this->assertStringContainsString('activationurl', $tpl);
        $this->assertStringContainsString('welcome_telegram', $tpl);
        $this->assertStringContainsString('welcomeimageurl', $tpl);
    }

    public function test_welcome_support_is_clickable_and_copy_is_warmer(): void {
        $root = dirname(__DIR__, 3);
        $defaults = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );
        $fr = (string)file_get_contents($root . '/lang/fr/local_subscriptions.php');

        $this->assertStringContainsString('mailto:{support_email}', $defaults);
        $this->assertStringContainsString('commerce_guest_activation_email_expiry_soft', $fr);
        $this->assertStringContainsString(
            'Nous vous souhaitons beaucoup de plaisir à chaque petite victoire',
            $defaults
        );
    }
}
