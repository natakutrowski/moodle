<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n712_test extends advanced_testcase {
    public function test_individual_grant_is_sku_free_and_supports_silent_mail_template_choice(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/subscriptions/add.php'
        );
        $renderer = file_get_contents(
            $root . '/renderer/user_subs_renderer.php'
        );

        self::assertStringNotContainsString(
            ". ' · ' . (string)\$product->sku",
            $page
        );
        foreach ([
            'CommerceGrantMailStudioSelection',
            'mailtemplateid',
            'commerce_manual_grant_silent_help',
            'commerce_manual_grant_mail_template',
            'crm-grant-mail-check',
        ] as $needle) {
            self::assertStringContainsString($needle, $page . $renderer);
        }
    }

    public function test_bulk_grant_is_sku_free_links_client_names_and_has_spacing(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );

        self::assertStringNotContainsString(
            "'label' => \$name . ' · ' . (string)\$product->sku",
            $source
        );
        foreach ([
            'crm-offers-access-preview-client-link',
            'crm-grant-selection-actions',
            'crm-grant-mail-check',
            'commerce_bulk_grant_silent_help',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
        self::assertStringNotContainsString(
            'commerce_offers_access_config_open_user360',
            $source
        );
    }

    public function test_grant_mail_studio_template_is_frozen_and_used_by_runtime(): void {
        $root = dirname(__DIR__, 3);

        $bridge = file_get_contents(
            $root
            . '/classes/commerce/mail/service/'
            . 'CommerceGrantMailStudioSelection.php'
        );
        $service = file_get_contents(
            $root
            . '/classes/commerce/grant/'
            . 'CommerceBulkGrantCampaignService.php'
        );
        $template = file_get_contents(
            $root
            . '/classes/commerce/mail/template/'
            . 'CommerceGrantAccessTemplate.php'
        );

        foreach ([
            'CATEGORY_TRANSACTIONAL',
            'STATUS_ACTIVE',
            'template_options',
            'snapshot',
            'translations',
        ] as $needle) {
            self::assertStringContainsString($needle, $bridge);
        }
        self::assertStringContainsString(
            'mailtemplatesnapshot',
            $service
        );
        self::assertStringContainsString(
            'CommerceGrantMailStudioSelection::create()',
            $template
        );
    }

    public function test_bulk_grant_campaigns_can_schedule_and_run_now_without_cron(): void {
        $root = dirname(__DIR__, 3);
        $service = file_get_contents(
            $root
            . '/classes/commerce/grant/'
            . 'CommerceBulkGrantCampaignService.php'
        );
        $view = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );
        $form = file_get_contents(
            $root . '/admin/commerce/grants/bulk.php'
        );

        foreach ([
            'public function run_now',
            'public function schedule',
            'scheduledat',
            'scheduledat > time()',
        ] as $needle) {
            self::assertStringContainsString($needle, $service);
        }
        foreach ([
            "'value' => 'runnow'",
            "'value' => 'schedule'",
            'commerce_bulk_grant_campaign_run_now',
            'commerce_bulk_grant_schedule_at',
        ] as $needle) {
            self::assertStringContainsString($needle, $view);
        }
        self::assertStringContainsString(
            'schedule_enabled',
            $form
        );
    }

    public function test_campaign_tracking_shows_last_update_for_grants_and_offers(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            '/admin/commerce/grants/campaign_view.php',
            '/admin/commerce/personal-offers/campaign_view.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString(
                'commerce_offers_access_last_update',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'strftimedatetimeshort',
                $source,
                $relative
            );
        }
    }

    public function test_grant_campaign_schema_persists_template_and_schedule(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        foreach ([
            'mailtemplateid',
            'mailtemplatesnapshot',
            'scheduledat',
            'status_schedule_idx',
        ] as $needle) {
            self::assertStringContainsString($needle, $install);
            self::assertStringContainsString($needle, $upgrade);
        }
    }

    public function test_n712_bumps_plugin_version_for_database_changes(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
