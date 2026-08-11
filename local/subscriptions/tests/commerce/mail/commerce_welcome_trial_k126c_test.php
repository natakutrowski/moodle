<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_welcome_trial_k126c_test extends advanced_testcase {
    public function test_welcome_login_email_uses_trial_card_design(): void {
        $root = dirname(__DIR__, 3);

        $welcome = (string)file_get_contents(
            $root . '/templates/commerce/mail/account_activation.mustache'
        );
        $trial = (string)file_get_contents(
            $root . '/templates/commerce/mail/trial_welcome.mustache'
        );

        foreach ([
            'background:#f8fafc',
            'border:1px solid #e5e7eb',
            'border-radius:12px',
            "font-family:'Courier New',Courier,monospace",
        ] as $style) {
            $this->assertStringContainsString($style, $welcome);
            $this->assertStringContainsString($style, $trial);
        }

        $this->assertStringContainsString('mailto:{{accountemail}}', $welcome);
    }

    public function test_trial_fr_en_support_is_clickable_even_for_persisted_old_copy(): void {
        $root = dirname(__DIR__, 3);

        $defaults = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php'
        );
        $abstract = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );

        $this->assertStringContainsString('mailto:{support_email}', $defaults);
        $this->assertStringContainsString(
            'CommerceMailType::TRIAL_WELCOME',
            $abstract
        );
        $this->assertStringContainsString(
            "strpos(\$resolved, 'mailto:' . \$supportemail)",
            $abstract
        );
    }
}
