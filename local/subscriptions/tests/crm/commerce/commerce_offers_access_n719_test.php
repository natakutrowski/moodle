<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n719_test extends advanced_testcase {
    public function test_reported_n716_and_n77_contracts_follow_current_ui(): void {
        $root = dirname(__DIR__, 3);
        $n716 = file_get_contents(
            $root . '/tests/crm/commerce/commerce_offers_access_n716_test.php'
        );
        $n77 = file_get_contents(
            $root . '/tests/crm/commerce/commerce_offers_access_n77_test.php'
        );

        self::assertStringContainsString(
            'crm-campaign-action-outline',
            $n716
        );
        self::assertStringNotContainsString(
            'crm-campaign-view-button',
            $n716
        );
        self::assertStringContainsString(
            'crm-result-scope-pill',
            $n77
        );
    }

    public function test_bulk_grant_mail_has_campaign_identity_and_unique_idempotency(): void {
        $root = dirname(__DIR__, 3);
        $service = file_get_contents(
            $root . '/classes/commerce/mail/service/CommerceGrantAccessMailService.php'
        );
        $campaign = file_get_contents(
            $root . '/classes/commerce/grant/CommerceBulkGrantCampaignService.php'
        );
        $context = file_get_contents(
            $root . '/classes/commerce/mail/context/CommerceGrantAccessMailContextFactory.php'
        );

        foreach ([
            'grant-campaign:',
            "'campaignid' => (int)\$campaign->id",
            "'campaignkey' => (string)\$campaign->campaignkey",
            "'memberid' => (int)\$member->id",
        ] as $needle) {
            self::assertStringContainsString(
                $needle,
                $service . $campaign
            );
        }
        self::assertStringContainsString(
            "'grantcampaign' => \$campaigncontext",
            $context
        );
    }

    public function test_grant_campaign_communication_reads_true_mail_engine_status(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/grants/campaign_view.php'
        );
        $service = file_get_contents(
            $root . '/classes/commerce/mail/service/CommerceGrantCampaignMailService.php'
        );

        self::assertStringContainsString(
            'CommerceGrantCampaignMailService::create()->summary',
            $page
        );
        self::assertStringContainsString(
            "\$mailcampaignsummary['sent']",
            $page
        );
        self::assertStringContainsString(
            'contextjson LIKE :campaignneedle',
            $service
        );
    }

    public function test_grant_mail_journal_links_back_to_campaign(): void {
        $root = dirname(__DIR__, 3);
        $resolver = file_get_contents(
            $root . '/classes/commerce/mail/admin/CommerceMailAdminContextResolver.php'
        );
        $journal = file_get_contents(
            $root . '/admin/commerce/mail/index.php'
        );

        self::assertStringContainsString(
            "\$context['grantcampaign']",
            $resolver
        );
        self::assertStringContainsString(
            '/admin/commerce/grants/campaign_view.php',
            $resolver
        );
        self::assertStringContainsString(
            'commerce_mail_action_open_grant_campaign',
            $journal
        );
    }

    public function test_campaign_member_tables_support_sorting_and_pagination(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            '/admin/commerce/personal-offers/campaign_view.php',
            '/admin/commerce/grants/campaign_view.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            foreach ([
                "optional_param('membersort'",
                "optional_param('memberdir'",
                "optional_param('memberpage'",
                "optional_param('memberperpage'",
                'crm-campaign-sort-link',
                'paging_bar',
            ] as $needle) {
                self::assertStringContainsString(
                    $needle,
                    $source,
                    $relative
                );
            }
        }
    }

    public function test_offer_campaign_excluded_member_does_not_show_mail_to_prepare(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        self::assertStringContainsString(
            'commerce_campaign_mail_not_applicable',
            $source
        );
        self::assertStringContainsString(
            '$mailnotapplicable',
            $source
        );
    }

    public function test_paginated_offer_selection_updates_only_visible_members(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );
        $manager = file_get_contents(
            $root
            . '/classes/commerce/personaloffer/campaign/'
            . 'CommercePersonalOfferCampaignManager.php'
        );

        self::assertStringContainsString(
            "'value' => 'selectionpage'",
            $page
        );
        self::assertStringContainsString(
            "'name' => 'visiblemembers[]'",
            $page
        );
        self::assertStringContainsString(
            'public function update_visible_member_selection',
            $manager
        );
    }

    public function test_n719_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
