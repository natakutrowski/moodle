<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_configuration_n1032_test extends \advanced_testcase {
    public function test_payment_editor_uses_friendly_technical_names_and_translated_stripe_keys(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/section.php');
        $this->assertStringContainsString("'local_subscriptions | ' . s(\$key)", $source);
        $this->assertStringNotContainsString("settings:stripe_reconciliation_cron_enabled", $source);
        $this->assertStringContainsString("'stripe_reconciliation_cron_enabled', 'stripe_reconciliation_cron_enabled', 'stripe_reconciliation_cron_enabled_desc'", $source);
        $this->assertStringContainsString("commerce_configuration_stripe_environment", $source);
        $this->assertStringContainsString("commerce_configuration_alfa_environment", $source);
        $this->assertStringContainsString("pix/providers/stripe.svg", $source);
        $this->assertStringContainsString("pix/providers/alfa.svg", $source);
    }

    public function test_payment_editor_language_strings_exist_in_all_supported_languages(): void {
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(__DIR__ . '/../../../lang/' . $lang . '/local_subscriptions.php');
            $this->assertStringContainsString("commerce_configuration_stripe_environment", $source);
            $this->assertStringContainsString("commerce_configuration_alfa_environment", $source);
            $this->assertStringContainsString("commerce_configuration_stripe_reconciliation_batch_size_desc", $source);
        }
    }
}
