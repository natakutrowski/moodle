<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignValidityService;

final class commerce_personal_offer_campaign_validity_m3g1_test extends advanced_testcase {
    public function test_fixed_paris_datetime_is_converted_to_authoritative_timestamp(): void {
        $this->resetAfterTest(true);

        $timestamp = CommercePersonalOfferCrmInput::datetime_local('2026-08-14T12:00', 'Europe/Paris');
        $expected = (new \DateTimeImmutable('2026-08-14 12:00:00', new \DateTimeZone('Europe/Paris')))
            ->getTimestamp();

        $this->assertSame($expected, $timestamp);
        $this->assertSame('2026-08-14 10:00', gmdate('Y-m-d H:i', (int)$timestamp));
    }

    public function test_nonexistent_dst_local_time_is_rejected(): void {
        $this->resetAfterTest(true);
        $this->expectException(\coding_exception::class);
        CommercePersonalOfferCrmInput::datetime_local('2026-03-29T02:30', 'Europe/Paris');
    }

    public function test_duration_is_materialised_from_each_offer_issuance_time(): void {
        $this->resetAfterTest(true);
        $issuedat = 1786464000;
        $campaign = (object)[
            'validitymode' => CommercePersonalOfferCampaignValidityService::MODE_DURATION,
            'validityduration' => CommercePersonalOfferCampaignValidityService::duration_seconds(48, 'hours'),
        ];

        $window = (new CommercePersonalOfferCampaignValidityService())->resolve($campaign, $issuedat);

        $this->assertSame($issuedat, $window['validfrom']);
        $this->assertSame($issuedat + (48 * HOURSECS), $window['expiresat']);
    }

    public function test_legacy_campaign_keeps_existing_absolute_window(): void {
        $this->resetAfterTest(true);
        $campaign = (object)[
            'validitymode' => CommercePersonalOfferCampaignValidityService::MODE_LEGACY,
            'validfrom' => 1000,
            'expiresat' => 2000,
        ];

        $window = (new CommercePersonalOfferCampaignValidityService())->resolve($campaign, 1500);
        $this->assertSame(['validfrom' => 1000, 'expiresat' => 2000], $window);
    }

    public function test_campaign_issuance_and_mail_renderer_use_resolved_validity_policy(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );
        $preview = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferCampaignMailPreviewService.php'
        );
        $mail = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        $this->assertStringContainsString('CommercePersonalOfferCampaignValidityService())->resolve($campaign, time())', $manager);
        $this->assertStringContainsString("\$validity['validfrom']", $manager);
        $this->assertStringContainsString("\$validity['expiresat']", $manager);
        $this->assertStringContainsString('previewvalidity', $preview);
        $this->assertStringContainsString("'validitytimezone'", $mail);
        $this->assertStringContainsString('strftimedatetimeshort', $template);
    }

    public function test_database_upgrade_preserves_existing_campaigns_as_legacy(): void {
        $root = dirname(__DIR__, 3);
        $upgrade = (string)file_get_contents($root . '/db/upgrade.php');
        $install = (string)file_get_contents($root . '/db/install.xml');

        $this->assertStringContainsString("new xmldb_field('validitymode'", $upgrade);
        $this->assertStringContainsString("'legacy'", $upgrade);
        $this->assertStringContainsString('upgrade_plugin_savepoint(true, 2026081103', $upgrade);
        $this->assertStringContainsString('NAME="validitymode"', $install);
        $this->assertStringContainsString('DEFAULT="legacy"', $install);
    }
}
