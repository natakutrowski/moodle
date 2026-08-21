<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n713_test extends advanced_testcase {
    public function test_new_grant_user_has_language_selector_defaulting_to_russian(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/subscriptions/add.php'
        );
        $renderer = file_get_contents(
            $root . '/renderer/user_subs_renderer.php'
        );

        self::assertStringContainsString(
            "'lang' => \$language",
            $page
        );
        self::assertStringContainsString(
            "'commerce_manual_grant_user_language'",
            $renderer
        );
        self::assertStringContainsString(
            "'lang',\n                        'ru'",
            $renderer
        );
    }

    public function test_commerce_access_radio_is_first_and_selected_by_default(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root . '/renderer/user_subs_renderer.php'
        );

        $native = strpos(
            $renderer,
            "'value' => 'native'"
        );
        $legacy = strpos(
            $renderer,
            "'value' => 'legacy'",
            $native
        );

        self::assertNotFalse($native);
        self::assertNotFalse($legacy);
        self::assertLessThan($legacy, $native);
        $grantworkspace = strpos(
            $renderer,
            'if ($grantworkspace) {',
            $native - 500
        );
        self::assertNotFalse($grantworkspace);

        $nativechecked = strpos(
            $renderer,
            "'checked' => 'checked'",
            $native
        );
        self::assertNotFalse($nativechecked);
        self::assertLessThan($legacy, $nativechecked);
    }

    public function test_grants_index_reuses_sales_filter_and_period_scope(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );

        foreach ([
            'crm-sales-filter-panel',
            'crm-sales-filter-grid',
            'crm-result-scope-pill',
            'commerce_result_scope_period_range',
            'strftimedatetimeshort',
            "'custom'",
            'grant-custom-period',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_grant_table_actions_use_blue_display_button_and_context_menu(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );

        foreach ([
            'fa fa-eye me-1',
            'crm-grant-action-outline',
            'crm-sales-row-menu-toggle',
            'crm-sales-row-menu-section',
            'commerce_grant_menu_mail_journal',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_recent_grant_campaigns_have_icon_and_visual_container(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/grants/index.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString(
            'fa fa-clock-o me-2',
            $page
        );
        self::assertStringContainsString(
            '.local-subscriptions-commerce-grants-page'
                . "\n"
                . '.crm-offers-access-recent',
            $styles
        );
        self::assertStringContainsString(
            'background: #f8fafc;',
            $styles
        );
    }

    public function test_running_campaign_can_bypass_grant_cron_and_queue_all_mails(): void {
        $root = dirname(__DIR__, 3);
        $view = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );
        $service = file_get_contents(
            $root
            . '/classes/commerce/grant/'
            . 'CommerceBulkGrantCampaignService.php'
        );

        self::assertStringContainsString(
            'commerce_bulk_grant_campaign_process_all_now',
            $view
        );
        self::assertStringContainsString(
            "STATUS_QUEUED,\n    CommerceBulkGrantCampaignService::STATUS_RUNNING",
            $view
        );
        self::assertStringContainsString(
            'public function run_now',
            $service
        );
        self::assertStringContainsString(
            'CommerceGrantAccessMailService::create()->queue',
            $service
        );
    }

    public function test_n713_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
