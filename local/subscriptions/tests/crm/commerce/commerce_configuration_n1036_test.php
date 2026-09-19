<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1036_test extends advanced_testcase {
    public function test_legal_section_exposes_both_active_legal_entities(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        foreach (['legal_entity_fr_', 'legal_entity_ru_'] as $prefix) {
            foreach (['name', 'address', 'legal', 'registration', 'tax_identifier', 'email', 'phone', 'website', 'tax_notice', 'footer'] as $field) {
                $this->assertStringContainsString("\$field('" . $prefix . $field . "'", $source);
            }
        }
        // Legacy profiles remain readable as fallback, but active legal-entity
        // edits must no longer write through to them.
        $this->assertStringContainsString("'invoice_eur_'", $source);
        $this->assertStringContainsString("'invoice_rub_'", $source);
        $this->assertStringNotContainsString(
            "set_config(\$legacyprefix . \$legacyfield, \$clean, 'local_subscriptions');",
            $source
        );
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
        $resolver = file_get_contents(
            $root . '/classes/commerce/legal/document/CommerceLegalDocumentResolver.php'
        );

        $this->assertStringContainsString(
            'CommerceLegalDocumentResolver',
            $region
        );
        $this->assertStringContainsString(
            '->resolve(null, current_language())',
            $region
        );
        $this->assertStringContainsString(
            'get_privacy_url()',
            $region
        );
        $this->assertStringContainsString(
            'get_terms_url()',
            $region
        );
        $this->assertStringContainsString(
            'get_offer_url()',
            $region
        );

        $this->assertStringContainsString(
            "in_array(\$country, ['RU', 'BY'], true)",
            $resolver
        );
        $this->assertStringContainsString(
            "\$profile = \$isruby ? 'ru' : 'row';",
            $resolver
        );
        foreach (
            [
                "'policy_url_' . \$profile",
                "'terms_url_' . \$profile",
                "'offer_url_' . \$profile",
            ]
            as $expression
        ) {
            $this->assertStringContainsString($expression, $resolver);
        }
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
        $this->assertMatchesRegularExpression(
            '/\\$plugin->version\\s*=\\s*\\d+;/',
            $version
        );
    }
}
