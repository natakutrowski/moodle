<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n714_test extends advanced_testcase {
    public function test_reported_n713_and_n712_contracts_are_fixed(): void {
        $root = dirname(__DIR__, 3);

        $n713 = file_get_contents(
            $root . '/tests/crm/commerce/commerce_offers_access_n713_test.php'
        );
        self::assertStringNotContainsString(
            "\"'value' => 'native',\\n                'checked' => 'checked'\"",
            $n713
        );

        $n712 = file_get_contents(
            $root . '/tests/crm/commerce/commerce_offers_access_n712_test.php'
        );
        self::assertStringContainsString(
            "\"'value' => 'runnow'\"",
            $n712
        );
    }

    public function test_individual_and_bulk_grants_expose_real_mail_preview(): void {
        $root = dirname(__DIR__, 3);

        $renderer = file_get_contents(
            $root . '/renderer/user_subs_renderer.php'
        );
        $bulk = file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );
        $preview = file_get_contents(
            $root . '/admin/commerce/grants/mail_preview.php'
        );

        foreach ([
            'manual-grant-email-preview',
            'data-preview-base',
            'refreshMailPreview',
        ] as $needle) {
            self::assertStringContainsString($needle, $renderer);
        }

        foreach ([
            'bulk-grant-email-preview',
            'targetproductid',
            'bulk-mail-template',
        ] as $needle) {
            self::assertStringContainsString($needle, $bulk);
        }

        foreach ([
            'CommerceMailType::GRANT_ACCESS',
            'CommerceGrantMailStudioSelection',
            'CommerceMailPreviewRenderer::render',
            'mailtemplatesnapshot',
        ] as $needle) {
            self::assertStringContainsString($needle, $preview);
        }
    }

    public function test_grants_list_uses_business_product_translations(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );

        self::assertStringContainsString(
            'CommercePersonalOfferCrmPresentation::business_product_label',
            $source
        );
    }

    public function test_recent_campaigns_only_highlight_first_row(): void {
        $root = dirname(__DIR__, 3);
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            '.crm-offers-access-recent-link:first-of-type',
            $styles
        );
        self::assertStringContainsString(
            'background: #f6f8fb;',
            $styles
        );
        self::assertStringContainsString(
            '.crm-offers-access-recent-row {'
                . "\n"
                . '    background: #fff;',
            $styles
        );
    }

    public function test_campaign_members_link_name_to_user360_without_separate_link(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );

        self::assertStringContainsString(
            'crm-offers-access-preview-client-link',
            $source
        );
        self::assertStringNotContainsString(
            "get_string('commerce_offers_access_config_open_user360'",
            $source
        );
    }

    public function test_n714_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
