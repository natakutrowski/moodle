<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n103_test extends advanced_testcase {
    public function test_configuration_sections_are_editable_and_expose_get_config_keys(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("set_config(\$key, \$clean, 'local_subscriptions')", $source);
        $this->assertStringContainsString("'local_subscriptions | ' . s(\$key)", $source);
        $this->assertStringContainsString("confirm_sesskey()", $source);
        $this->assertStringContainsString("commerce_configuration_edit_notice", $source);
    }

    public function test_configuration_editor_reuses_translated_setting_labels_and_descriptions(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertStringContainsString("'provider_default_desc'", $source);
        $this->assertStringContainsString("'commerce_configuration_default_user_language_desc'", $source);
        $this->assertStringContainsString("'settings:guest_checkout_cleanup_enabled_desc'", $source);
        $this->assertStringContainsString("'commerce_configuration_runtime_mode_desc'", $source);
    }

    public function test_configuration_editor_does_not_expose_payment_secrets(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertStringNotContainsString('stripe_test_secret', $source);
        $this->assertStringNotContainsString('alfa_test_password', $source);
        $this->assertStringNotContainsString('email_link_secret', $source);
    }
}
