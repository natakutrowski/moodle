<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n74_test extends advanced_testcase {
    public function test_shared_campaign_renderer_exposes_workflow_metrics_and_communication(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessCampaignRenderer.php'
        );

        foreach ([
            'public static function workflow',
            'public static function metrics',
            'public static function communication',
            'public static function technical',
            'crm-offers-access-campaign-workflow',
            'commerce_offers_access_campaign_open_mail_journal',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_offer_campaign_view_uses_unified_offers_access_monitoring(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        foreach ([
            'CommerceOffersAccessNavigationRenderer::CAMPAIGNS',
            'CommerceOffersAccessCampaignRenderer::workflow',
            'CommerceOffersAccessCampaignRenderer::communication',
            'CommerceOffersAccessCampaignRenderer::technical',
            'commerce_offers_access_campaign_step_audience',
            'commerce_offers_access_campaign_summary_title',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            "CommerceSectionNavigationRenderer::PERSONAL_OFFERS",
            $source
        );
    }

    public function test_grant_campaign_view_prioritises_business_information(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );

        foreach ([
            'CommerceOffersAccessNavigationRenderer::CAMPAIGNS',
            'CommerceOffersAccessCampaignRenderer::workflow',
            'CommerceOffersAccessCampaignRenderer::technical',
            'crm-offers-access-preview-client-name',
            'commerce_offers_access_campaign_member_details',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            "CommerceSectionNavigationRenderer::GRANTS",
            $source
        );
        self::assertStringNotContainsString(
            "get_string('commerce_bulk_grant_evidence', 'local_subscriptions'),",
            $source
        );
    }

    public function test_n74_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
