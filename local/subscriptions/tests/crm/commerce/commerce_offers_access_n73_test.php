<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n73_test extends advanced_testcase {
    public function test_shared_configuration_renderer_exposes_progressive_sections_and_summary(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessConfigurationRenderer.php'
        );

        foreach ([
            'start_layout',
            'start_section',
            'advanced',
            'summary',
            'crm-offers-access-config-summary',
            'commerce_offers_access_config_mailstudio_title',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_offer_campaign_hides_technical_configuration_behind_advanced_sections(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_edit.php'
        );

        foreach ([
            'CommerceOffersAccessConfigurationRenderer::start_layout',
            'commerce_offers_access_config_technical',
            'commerce_offers_access_config_advanced_audience',
            'crm-offers-access-advanced',
            'n73-summary-product',
            "'category' => 'personal_offer'",
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_individual_offer_uses_progressive_context_and_live_summary(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/create.php'
        );

        foreach ([
            'commerce_offers_access_config_beneficiary_title',
            'commerce_offers_access_config_context_title',
            'commerce_offers_access_config_communication',
            'n73-individual-summary-beneficiary',
            'n73-individual-summary-product',
            'refresh()',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_bulk_grant_preview_prioritises_business_information(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );

        self::assertStringContainsString(
            'commerce_offers_access_config_bulk_audience_title',
            $source
        );
        self::assertStringContainsString(
            'crm-offers-access-preview-client-name',
            $source
        );
        self::assertStringContainsString(
            'commerce_offers_access_config_technical_evidence',
            $source
        );
        self::assertStringContainsString(
            "'category' => 'transactional'",
            $source
        );

        self::assertStringNotContainsString(
            "get_string('commerce_bulk_grant_evidence', 'local_subscriptions'),",
            $source
        );
        self::assertStringNotContainsString(
            "s((string)\$simulation['target']['sku'])",
            $source
        );
    }

    public function test_guided_manual_access_uses_same_configuration_shell(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/subscriptions/add.php'
        );

        self::assertStringContainsString(
            'CommerceOffersAccessConfigurationRenderer::start_layout',
            $source
        );
        self::assertStringContainsString(
            'commerce_offers_access_config_manual_access_title',
            $source
        );
        self::assertStringContainsString(
            'commerce_offers_access_config_summary_grant',
            $source
        );
    }

    public function test_n73_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
