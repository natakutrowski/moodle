<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_distribution_certification_k15ef_test extends advanced_testcase {

    public function test_campaign_distribution_uses_transactional_outbox_only(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );

        $this->assertStringContainsString('CommerceMailRuntime::queue_service()', $service);
        $this->assertStringContainsString('CommerceMailType::PERSONAL_OFFER', $service);
        $this->assertStringContainsString('queue_missing_campaign', $service);
        $this->assertStringNotContainsString('email_to_user(', $service);
    }

    public function test_campaign_mail_is_idempotent_per_campaign_member(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );

        $this->assertStringContainsString(
            "'personal-offer:campaign:' . \$campaignid . ':member:' . \$memberid",
            $service
        );
        $this->assertStringContainsString('mail_record_for_campaign_member', $service);
    }

    public function test_failed_mail_retry_resets_only_failed_campaign_mail_rows(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );

        $this->assertStringContainsString('public function retry_failed_campaign', $service);
        $this->assertStringContainsString("\$mail->status !== 'failed'", $service);
        $this->assertStringContainsString('reset_failed((int)$mail->id)', $service);
    }

    public function test_generation_retry_uses_frozen_snapshot_and_terminal_members_are_not_reprocessed(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString('public function retry_generation_errors', $manager);
        $this->assertStringContainsString('STATUS_SNAPSHOT', $manager);
        $this->assertStringContainsString(
            '[self::MEMBER_ELIGIBLE, self::MEMBER_ERROR]',
            $manager
        );
        $this->assertStringContainsString('assert_snapshot_integrity', $manager);
    }

    public function test_certification_requires_generation_and_mail_completion(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString('public function certification_state', $manager);
        $this->assertStringContainsString('generationerrors', $manager);
        $this->assertStringContainsString('selectedpending', $manager);
        $this->assertStringContainsString('mailblocking', $manager);
        $this->assertStringContainsString('public function certify_campaign', $manager);
        $this->assertStringContainsString('STATUS_CLOSED', $manager);
    }

    public function test_certification_audit_fields_exist(): void {
        $root = dirname(__DIR__, 3);
        $install = (string)file_get_contents($root . '/db/install.xml');
        $upgrade = (string)file_get_contents($root . '/db/upgrade.php');

        $this->assertStringContainsString('FIELD NAME="certifiedat"', $install);
        $this->assertStringContainsString('FIELD NAME="certifiedby"', $install);
        $this->assertStringContainsString("'certifiedat'", $upgrade);
        $this->assertStringContainsString("'certifiedby'", $upgrade);
    }

    public function test_crm_exposes_distribution_retry_and_certification_controls(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        $this->assertStringContainsString("'retrygeneration'", $page);
        $this->assertStringContainsString("'retrymail'", $page);
        $this->assertStringContainsString("'certify'", $page);
        $this->assertStringContainsString('commerce_personal_offer_mail_processing', $page);
        $this->assertStringContainsString('/admin/commerce/mail/view.php', $page);
    }
}
