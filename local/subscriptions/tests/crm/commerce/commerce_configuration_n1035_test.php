<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1035_test extends advanced_testcase {
    public function test_communications_section_covers_mail_engine_workers_and_global_limit(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertIsString($source);

        foreach ([
            'commerce_mail_transactional_enabled',
            'commerce_mail_transactional_batch_size',
            'commerce_mail_transactional_hourly_limit',
            'personal_offer_mail_enabled',
            'personal_offer_mail_batch_size',
            'personal_offer_mail_hourly_limit',
            'commerce_mail_marketing_enabled',
            'commerce_mail_marketing_batch_size',
            'commerce_mail_marketing_hourly_limit',
            'commerce_mail_global_hourly_limit',
        ] as $key) {
            $this->assertStringContainsString("\$field('" . $key . "'", $source);
        }
    }

    public function test_audit_generation_and_audit_worker_are_presented_as_distinct_controls(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $this->assertStringContainsString("\$field('commerce_mail_audit_copy_enabled'", $source);
        $this->assertStringContainsString("\$field('commerce_mail_audit_enabled'", $source);
        $this->assertStringContainsString('commerce_configuration_audit_copy_generation_label', $source);
        $this->assertStringContainsString('commerce_configuration_audit_worker_label', $source);
    }

    public function test_legacy_automatic_mail_policy_is_exposed_in_communications(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        foreach ([
            'legacy_auto_mail_enabled',
            'legacy_auto_payment_reminders_enabled',
            'legacy_auto_expiry_reminders_enabled',
            'legacy_auto_lifecycle_emails_enabled',
        ] as $key) {
            $this->assertStringContainsString("\$field('" . $key . "'", $source);
        }
        $this->assertStringContainsString('commerce-config-section-card--legacy', $source);
    }

    public function test_communications_links_to_operational_mail_engine_screen(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $this->assertStringContainsString('/admin/commerce/mail/configuration.php', $source);
        $this->assertStringContainsString('commerce_configuration_communications_mail_engine_note', $source);
    }

    public function test_legacy_and_native_copy_destination_share_one_configuration_field(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $this->assertStringContainsString("\$field('commerce_mail_audit_copy_address'", $source);
        $this->assertStringNotContainsString("\$field('email_copy_to'", $source);
        $this->assertStringContainsString("set_config('email_copy_to', \$clean, 'local_subscriptions')", $source);

        $mailer = file_get_contents($root . '/classes/mailer.php');
        $this->assertStringContainsString("get_config('local_subscriptions', 'commerce_mail_audit_copy_address')", $mailer);
        $this->assertStringContainsString("get_config('local_subscriptions', 'email_copy_to')", $mailer);
    }

    public function test_n1035_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $this->assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
