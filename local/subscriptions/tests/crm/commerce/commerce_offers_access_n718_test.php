<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n718_test extends advanced_testcase {
    public function test_offer_campaign_audience_has_search_status_and_mail_filters(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        foreach ([
            "optional_param('memberq'",
            "optional_param('memberstatus'",
            "optional_param('mailstatus'",
            'crm-campaign-member-filters',
            'crm-campaign-member-filter-grid',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_offer_campaign_simulation_note_is_discreet_and_statuses_are_badges(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'crm-offer-campaign-simulation-note',
            $page
        );
        self::assertStringNotContainsString(
            "'alert alert-info mt-4'",
            $page
        );
        self::assertStringContainsString(
            'crm-campaign-member-badge',
            $page
        );
        self::assertStringContainsString(
            'CommerceMailAdminPresentation::status_badge_class',
            $page
        );
        self::assertStringContainsString(
            '.crm-campaign-mail-badge',
            $styles
        );
    }

    public function test_grant_campaign_members_have_search_and_status_filters(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );

        foreach ([
            "optional_param('memberq'",
            "optional_param('memberstatus'",
            'crm-campaign-member-filters',
            'grant-member-query',
            'grant-member-status',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_running_grant_notice_is_before_kpis(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );

        $notice = strpos(
            $source,
            'commerce_bulk_grant_campaign_cron_notice'
        );
        $metrics = strpos(
            $source,
            'CommerceOffersAccessCampaignRenderer::metrics(['
        );

        self::assertNotFalse($notice);
        self::assertNotFalse($metrics);
        self::assertLessThan($metrics, $notice);
    }

    public function test_campaign_member_tables_are_compact(): void {
        $root = dirname(__DIR__, 3);
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            '.crm-offer-campaign-audience-table th,',
            $styles
        );
        self::assertStringContainsString(
            '.crm-grant-campaign-members-table th,',
            $styles
        );
        self::assertStringContainsString(
            'padding-top: .48rem;',
            $styles
        );
    }

    public function test_n718_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
