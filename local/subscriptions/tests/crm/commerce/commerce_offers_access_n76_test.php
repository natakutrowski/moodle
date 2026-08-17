<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n76_test extends advanced_testcase {
    public function test_shared_detail_renderer_exposes_business_first_components(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/crm/commerce/rendering/'
            . 'CommerceOffersAccessDetailRenderer.php'
        );

        foreach ([
            'public static function hero',
            'public static function panel',
            'public static function rows',
            'public static function actions',
            'public static function technical',
            'crm-offers-access-detail-hero',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_offer_detail_joins_offers_access_workspace_and_hides_technical_data(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/view.php'
        );

        foreach ([
            'CommerceSectionNavigationRenderer::OFFERS_ACCESS',
            'CommerceOffersAccessNavigationRenderer::OFFERS',
            'CommerceOffersAccessDetailRenderer::hero',
            'CommerceOffersAccessDetailRenderer::technical',
            'commerce_offers_access_offer_context',
            'commerce_offers_access_management',
            'commerce_offers_access_offer_mail_journal',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringNotContainsString(
            'CommerceSectionNavigationRenderer::PERSONAL_OFFERS',
            $source
        );
        self::assertStringNotContainsString(
            "echo \$OUTPUT->heading(get_string('commerce_personal_offer_metadata_technical'",
            $source
        );
    }

    public function test_grant_detail_page_prioritises_client_product_and_validity(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/view.php'
        );

        foreach ([
            'local_subs_commerce_grant',
            'CommerceOffersAccessDetailRenderer::hero',
            'commerce_offers_access_grant_access_title',
            'commerce_offers_access_grant_origin_title',
            'commerce_offers_access_grant_lifecycle_title',
            'commerce_offers_access_grant_mail_journal',
            'CommerceOffersAccessDetailRenderer::technical',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            "get_record(\n        'user'",
            $source
        );
        self::assertStringContainsString(
            "'*'",
            $source
        );
    }

    public function test_grants_list_links_each_row_to_detail_page(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );

        self::assertStringContainsString(
            '/admin/commerce/grants/view.php',
            $source
        );
        self::assertStringContainsString(
            'crm-offers-access-date-link',
            $source
        );
    }

    public function test_n76_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
