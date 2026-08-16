<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n77_test extends advanced_testcase {
    public function test_final_polish_renderer_has_empty_states_and_removable_filter_pills(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessPolishRenderer.php'
        );

        foreach ([
            'public static function empty_state',
            'public static function filter_pills',
            'crm-offers-access-filter-pill-remove',
            'commerce_offers_access_remove_filter',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_operational_lists_use_current_scope_and_empty_state_patterns(): void {
        $root = dirname(__DIR__, 3);

        $offers = file_get_contents(
            $root . '/admin/commerce/personal-offers/index.php'
        );
        self::assertStringContainsString(
            'crm-result-scope-pill',
            $offers
        );
        self::assertStringContainsString(
            'CommerceOffersAccessPolishRenderer::empty_state',
            $offers
        );

        $grants = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );
        self::assertStringContainsString(
            'crm-result-scope-pill',
            $grants
        );
        self::assertStringContainsString(
            'CommerceOffersAccessPolishRenderer::empty_state',
            $grants
        );

        $campaigns = file_get_contents(
            $root . '/admin/commerce/offers-access/campaigns.php'
        );
        self::assertStringContainsString(
            'crm-result-scope-pill',
            $campaigns
        );
        self::assertStringContainsString(
            'CommerceOffersAccessPolishRenderer::empty_state',
            $campaigns
        );
    }

    public function test_remaining_personal_offer_screens_use_offers_access_navigation(): void {
        $root = dirname(__DIR__, 3);

        $expected = [
            '/admin/commerce/personal-offers/edit.php'
                => 'CommerceOffersAccessNavigationRenderer::OFFERS',
            '/admin/commerce/personal-offers/stats.php'
                => 'CommerceOffersAccessNavigationRenderer::OFFERS',
            '/admin/commerce/personal-offers/campaign_email.php'
                => 'CommerceOffersAccessNavigationRenderer::CAMPAIGNS',
            '/admin/commerce/personal-offers/campaign_email_preview.php'
                => 'CommerceOffersAccessNavigationRenderer::CAMPAIGNS',
        ];

        foreach ($expected as $relative => $localnav) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString(
                'CommerceSectionNavigationRenderer::OFFERS_ACCESS',
                $source,
                $relative
            );
            self::assertStringContainsString(
                $localnav,
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'CommerceSectionNavigationRenderer::PERSONAL_OFFERS',
                $source,
                $relative
            );
        }
    }

    public function test_legacy_offer_campaign_list_redirects_to_unified_workspace(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaigns.php'
        );

        self::assertStringContainsString(
            '/admin/commerce/offers-access/campaigns.php',
            $source
        );
        self::assertStringContainsString(
            "'kind' => 'offer'",
            $source
        );
        self::assertStringNotContainsString(
            'CommercePersonalOfferCampaignManager',
            $source
        );
    }

    public function test_campaign_editor_no_longer_returns_to_legacy_campaign_list(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_edit.php'
        );

        self::assertStringNotContainsString(
            "new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php')",
            $source
        );
        self::assertStringContainsString(
            "/admin/commerce/offers-access/campaigns.php",
            $source
        );
    }


    public function test_create_flow_uses_local_change_string_and_contextual_subnav(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/offers-access/create.php'
        );

        self::assertStringNotContainsString("get_string('change')", $source);
        self::assertStringContainsString(
            "get_string('commerce_offers_access_change', 'local_subscriptions')",
            $source
        );
        self::assertStringContainsString(
            'CommerceOffersAccessNavigationRenderer::CAMPAIGNS',
            $source
        );
        self::assertStringContainsString(
            'CommerceOffersAccessNavigationRenderer::OFFERS',
            $source
        );
        self::assertStringContainsString(
            'CommerceOffersAccessNavigationRenderer::GRANTS',
            $source
        );
    }

    public function test_attributions_are_scoped_to_manual_grants_only(): void {
        $root = dirname(__DIR__, 3);
        $list = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );
        $overview = file_get_contents(
            $root . '/admin/commerce/offers-access/index.php'
        );

        self::assertStringContainsString("'manual-u%'", $list);
        self::assertStringContainsString("'manual-u%'", $overview);
        self::assertStringContainsString(
            "sql_like('g.purchasereference'",
            $list
        );
    }

    public function test_individual_pages_expose_semantic_action_colours(): void {
        $root = dirname(__DIR__, 3);
        $offer = file_get_contents(
            $root . '/admin/commerce/personal-offers/view.php'
        );
        $grant = file_get_contents(
            $root . '/admin/commerce/grants/view.php'
        );

        foreach ([
            'is-client',
            'is-communication',
        ] as $class) {
            self::assertStringContainsString($class, $offer);
            self::assertStringContainsString($class, $grant);
        }
        self::assertStringContainsString('is-sale', $offer);
    }

    public function test_n77_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
