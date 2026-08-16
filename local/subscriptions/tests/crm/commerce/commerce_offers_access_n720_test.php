<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n720_test extends advanced_testcase {
    public function test_offer_campaign_sort_contract_is_defined_before_table_header(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        $definition = strpos(
            $source,
            "\$allowedmembersorts = ["
        );
        $header = strpos(
            $source,
            "\$table->head = ["
        );

        self::assertNotFalse($definition);
        self::assertNotFalse($header);
        self::assertLessThan($header, $definition);
    }

    public function test_grant_campaign_mail_summary_supports_new_and_historical_contexts(): void {
        $root = dirname(__DIR__, 3);
        $service = file_get_contents(
            $root
            . '/classes/commerce/mail/service/'
            . 'CommerceGrantCampaignMailService.php'
        );

        foreach ([
            'records_for_campaign',
            'campaign_for_mail',
            'grantcampaign',
            'rootproductid',
            'lastattemptat',
            'HISTORICAL_TIME_TOLERANCE',
        ] as $needle) {
            self::assertStringContainsString($needle, $service);
        }
    }

    public function test_grant_mail_context_resolves_to_campaign_not_product(): void {
        $root = dirname(__DIR__, 3);
        $resolver = file_get_contents(
            $root
            . '/classes/commerce/mail/admin/'
            . 'CommerceMailAdminContextResolver.php'
        );

        self::assertStringContainsString(
            'CommerceGrantCampaignMailService',
            $resolver
        );
        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/grants/campaign_view.php',
            $resolver
        );
        self::assertStringContainsString(
            '$contexturl = null;',
            $resolver
        );
    }

    public function test_mail_journal_only_labels_real_campaign_url_as_grant_campaign(): void {
        $root = dirname(__DIR__, 3);
        $journal = file_get_contents(
            $root . '/admin/commerce/mail/index.php'
        );

        self::assertStringContainsString(
            "\$grantcampaignpath = '/local/subscriptions/admin/commerce/grants/campaign_view.php'",
            $journal
        );
        self::assertStringContainsString(
            '$isgrantcampaignurl',
            $journal
        );
        self::assertStringContainsString(
            'commerce_mail_action_open_grant_campaign',
            $journal
        );
    }

    public function test_n720_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081601;',
            $version
        );
    }
}
