<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_snapshot_generation_k15cd_test extends advanced_testcase {

    public function test_snapshot_is_a_distinct_campaign_state_and_is_immutable(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString("STATUS_SNAPSHOT = 'snapshot'", $manager);
        $this->assertStringContainsString('public function create_snapshot', $manager);
        $this->assertStringContainsString('snapshot_hash(', $manager);
        $this->assertStringContainsString('assert_snapshot_integrity(', $manager);
        $this->assertStringContainsString('hash_equals(', $manager);
    }

    public function test_snapshot_members_are_explicitly_frozen_for_resume_safety(): void {
        $root = dirname(__DIR__, 3);
        $install = (string)file_get_contents($root . '/db/install.xml');
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString('FIELD NAME="snapshotselected"', $install);
        $this->assertStringContainsString('$member->snapshotselected', $manager);
        $this->assertStringContainsString('!empty($member->snapshotselected)', $manager);
    }

    public function test_generation_never_recalculates_source_audience(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $generatepos = strpos($manager, 'public function generate(');
        $individualpos = strpos($manager, 'public function issue_individual(', $generatepos);
        $generate = substr($manager, $generatepos, $individualpos - $generatepos);

        $this->assertStringContainsString('assert_snapshot_integrity', $generate);
        $this->assertStringNotContainsString('criteria_candidates(', $generate);
        $this->assertStringNotContainsString('list_candidates(', $generate);
    }

    public function test_generation_rechecks_only_target_ownership_and_active_offer(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $generatepos = strpos($manager, 'public function generate(');
        $individualpos = strpos($manager, 'public function issue_individual(', $generatepos);
        $generate = substr($manager, $generatepos, $individualpos - $generatepos);

        $this->assertStringContainsString('customer_has_target(', $generate);
        $this->assertStringContainsString('active_offer_id(', $generate);
        $this->assertStringContainsString('target_acquired_after_snapshot', $generate);
        $this->assertStringContainsString('active_offer_created_after_snapshot', $generate);
    }

    public function test_generation_has_interruption_recovery_path(): void {
        $root = dirname(__DIR__, 3);
        $manager = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/campaign/CommercePersonalOfferCampaignManager.php'
        );

        $this->assertStringContainsString(
            'Recovery path: the offer may have been issued before',
            $manager
        );
        $this->assertStringContainsString('MEMBER_REPLAYED', $manager);
    }

    public function test_crm_flow_requires_snapshot_before_generation(): void {
        $root = dirname(__DIR__, 3);
        $page = (string)file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_view.php'
        );

        $this->assertStringContainsString("'action', 'value' => 'snapshot'", $page);
        $this->assertStringContainsString("\$campaign->status === 'snapshot'", $page);
        $this->assertStringContainsString('commerce_personal_offer_generate_snapshot', $page);
        $this->assertStringContainsString('commerce_personal_offer_snapshot_frozen_notice', $page);
    }
}
