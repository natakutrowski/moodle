<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n715_test extends advanced_testcase {
    public function test_n712_schedule_contract_matches_generated_hidden_input(): void {
        $root = dirname(__DIR__, 3);
        $test = file_get_contents(
            $root . '/tests/crm/commerce/commerce_offers_access_n712_test.php'
        );

        self::assertStringContainsString(
            "\"'value' => 'schedule'\"",
            $test
        );
        self::assertStringNotContainsString(
            "\"'action' => 'schedule'\"",
            $test
        );
    }

    public function test_recent_grant_campaigns_highlight_header_not_first_data_row(): void {
        $root = dirname(__DIR__, 3);
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            '.crm-offers-access-panel-head {',
            $styles
        );
        self::assertStringContainsString(
            'background: #f6f8fb;',
            $styles
        );
        self::assertStringContainsString(
            '.crm-offers-access-recent-link:first-of-type'
                . "\n"
                . '.crm-offers-access-recent-row {',
            $styles
        );
        self::assertStringContainsString(
            'background: #fff;',
            $styles
        );
    }

    public function test_grant_campaign_has_five_horizontal_metrics_and_primary_total(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'CommerceOffersAccessCampaignRenderer::metrics([',
            $page
        );
        self::assertStringContainsString(
            "'class' => 'is-primary'",
            $page
        );
        self::assertStringContainsString(
            'grid-template-columns: repeat(5, minmax(0, 1fr));',
            $styles
        );
    }

    public function test_grant_campaign_uses_business_product_labels(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );

        self::assertStringContainsString(
            'CommercePersonalOfferCrmPresentation::business_product_label',
            $page
        );
        self::assertStringContainsString(
            'subscription_manager::get_translated_plan_name',
            $page
        );
        self::assertStringContainsString(
            '$sourcebusinesslabel',
            $page
        );
        self::assertStringContainsString(
            '$targetbusinesslabel',
            $page
        );
    }

    public function test_grant_campaign_communication_contains_collapsible_email_preview(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );
        $renderer = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessCampaignRenderer.php'
        );
        $preview = file_get_contents(
            $root . '/admin/commerce/grants/mail_preview.php'
        );

        self::assertStringContainsString(
            "'embed' => 1",
            $page
        );
        self::assertStringContainsString(
            'crm-offers-access-campaign-preview-frame',
            $renderer
        );
        self::assertStringContainsString(
            'if ($embed)',
            $preview
        );
    }

    public function test_offers_tab_is_pink_and_status_badges_are_larger(): void {
        $root = dirname(__DIR__, 3);
        $nav = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessNavigationRenderer.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            "self::OFFERS => ' is-offer'",
            $nav
        );
        self::assertStringContainsString(
            '.crm-offers-access-tab.is-offer.is-active',
            $styles
        );
        self::assertStringContainsString(
            '.crm-offers-access-list-table td:nth-child(5) .badge',
            $styles
        );
    }

    public function test_n715_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
