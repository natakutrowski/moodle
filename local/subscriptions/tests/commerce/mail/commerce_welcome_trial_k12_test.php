<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

final class commerce_welcome_trial_k12_test extends advanced_testcase {
    public function test_trial_welcome_is_registered_in_native_mail_engine(): void {
        $this->assertContains(CommerceMailType::TRIAL_WELCOME, CommerceMailType::all());
        $this->assertTrue(CommerceMailRuntime::template_registry()->has(CommerceMailType::TRIAL_WELCOME));
    }

    public function test_activation_has_telegram_and_key_cta_support(): void {
        $root = dirname(__DIR__, 3);
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommerceAccountActivationTemplate.php'
        );
        $renderer = (string)file_get_contents($root . '/classes/mail/MailRenderer.php');

        $this->assertStringContainsString('keyiconurl', $template);
        $this->assertStringContainsString('telegram-white.png', $template);
        $this->assertStringContainsString('key-white.png', $renderer);
        $this->assertStringContainsString('activationexpirestimestamp', $template);
    }

    public function test_guest_welcome_is_only_sent_for_provisional_guest_account(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/checkout/guest/CommerceGuestAccountActivator.php'
        );
        $this->assertStringContainsString(
            "\$isprovisional = (\$metadata['account_origin'] ?? '') === 'guest_checkout';",
            $source
        );
        $this->assertStringContainsString('if ($isprovisional) {', $source);
        $this->assertStringContainsString('$this->send_activation_email($user, $session);', $source);
    }

    public function test_old_persisted_activation_default_is_detected(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );
        $this->assertStringContainsString('legacyheadings', $source);
        $this->assertStringContainsString('CommerceMailType::ACCOUNT_ACTIVATION', $source);
    }

    public function test_legacy_trial_started_is_bridged_to_native_commerce_mail(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents($root . '/classes/mailer.php');
        $this->assertStringContainsString('CommerceTrialWelcomeMailService', $source);
        $this->assertStringContainsString('->queue_and_send($args)', $source);
    }
}
