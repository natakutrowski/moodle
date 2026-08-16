<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n75_test extends advanced_testcase {
    public function test_personal_offer_repository_supports_period_filters(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/personaloffer/repository/'
            . 'MoodleCommercePersonalOfferRepository.php'
        );

        self::assertStringContainsString(
            "filters['timecreatedfrom']",
            $source
        );
        self::assertStringContainsString(
            'o.timecreated >= :timecreatedfrom',
            $source
        );
    }

    public function test_offer_list_hides_technical_identifiers_from_primary_table(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/index.php'
        );

        foreach ([
            'commerce_offers_access_search_filters',
            'commerce_offers_access_offers_found',
            'crm-offers-access-list-table',
            'crm-offers-access-row-actions',
            'crm-offers-access-row-context',
            'commerce_offers_access_config_product',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            "get_string('commerce_personal_offer_id', 'local_subscriptions'),",
            $source
        );
        self::assertStringNotContainsString(
            "html_writer::tag('code', s(substr(\$offer->get_offer_uuid()",
            $source
        );
    }

    public function test_grants_workspace_reads_actual_grant_ledger_with_business_filters(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );

        foreach ([
            'local_subs_commerce_grant',
            'commerce_offers_access_grants_kpi_total',
            'commerce_offers_access_grants_search_placeholder',
            'crm-offers-access-kpis',
            'crm-offers-access-list-table',
            'commerce_offers_access_recent_grant_campaigns',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            "get_records_list('user', 'id'",
            $source
        );
        self::assertStringContainsString(
            "'*'",
            $source
        );
    }

    public function test_unified_campaigns_have_kpis_filters_and_progress(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/campaigns.php'
        );

        foreach ([
            'commerce_offers_access_campaign_kpi_total',
            'commerce_offers_access_campaign_state_active',
            'commerce_offers_access_campaign_search_placeholder',
            'crm-offers-access-progress-track',
            'crm-offers-access-progress-bar',
            'CommercePersonalOfferCampaignManager',
            'CommerceBulkGrantCampaignService',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_n75_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
