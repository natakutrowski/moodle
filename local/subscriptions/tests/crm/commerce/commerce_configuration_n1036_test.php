<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1036_test extends advanced_testcase {
    public function test_legal_section_exposes_both_active_invoice_profiles(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        foreach (['invoice_eur_', 'invoice_rub_'] as $prefix) {
            foreach (['name', 'address', 'legal', 'email', 'phone', 'website', 'tax_notice', 'footer'] as $field) {
                $this->assertStringContainsString("\$field('" . $prefix . $field . "'", $source);
            }
        }
    }

    public function test_invoice_profiles_are_consumed_by_native_invoice_resolver(): void {
        $root = dirname(__DIR__, 3);
        $resolver = file_get_contents($root . '/classes/commerce/order/invoice/CommerceInvoiceProfileResolver.php');

        $this->assertStringContainsString("\$currency === 'RUB' ? 'rub' : 'eur'", $resolver);
        $this->assertStringContainsString("'invoice_' . \$profile . '_name'", $resolver);
        $this->assertStringContainsString("'invoice_' . \$profile . '_footer'", $resolver);
    }

    public function test_legal_links_are_active_and_regionally_resolved(): void {
        $root = dirname(__DIR__, 3);
        $region = file_get_contents($root . '/classes/support/Region.php');

        foreach (['policy_url_ru', 'policy_url_row', 'terms_url_ru', 'terms_url_row', 'offer_url_ru', 'offer_url_row'] as $key) {
            $this->assertStringContainsString($key, $region);
        }
        $this->assertStringContainsString("['RU','BY']", $region);
    }

    public function test_legal_links_accept_relative_internal_paths_in_crm_editor(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $this->assertStringContainsString("\$field('policy_url_ru'", $source);
        $this->assertStringContainsString("'commerce_configuration_policy_document_label'", $source);
        $this->assertStringContainsString("'commerce_configuration_policy_url_ru_by_desc'", $source);
        $this->assertStringNotContainsString("\$field('policy_url_ru', 'policy_url_ru', 'commerce_configuration_url_setting_desc', 'url'", $source);
    }

    public function test_n1036_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $this->assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
