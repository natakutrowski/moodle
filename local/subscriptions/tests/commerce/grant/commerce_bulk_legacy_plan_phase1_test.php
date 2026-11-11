<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\grant\CommerceBulkGrantDryRunService;
use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;
use local_subscriptions\commerce\grant\CommerceBulkLegacyPlanGrantService;

final class commerce_bulk_legacy_plan_phase1_test extends advanced_testcase {
    /** @return array{0:int,1:int,2:int,3:int} user id, source plan id, target plan id, course id */
    private function fixture(bool $targetactive = false): array {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Bulk target scope ' . $user->id,
            'course_ids' => (string)$course->id,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $sourceid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Bulk source ' . $user->id,
            'accessscopeid' => $scopeid, 'duration_key' => '1year',
            'is_active' => 1, 'is_trial' => 0,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $targetid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Bulk target ' . $user->id,
            'accessscopeid' => $scopeid, 'duration_key' => '3months',
            'is_active' => $targetactive ? 1 : 0, 'is_trial' => 0,
            'creation_date' => $now, 'last_update' => $now,
        ]);
        $DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $sourceid,
            'payment_provider' => 'manual',
            'transactionid' => 'test-source-' . $user->id,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'status' => 'active',
            'start_date' => $now - 3600,
            'end_date' => $now + DAYSECS * 365,
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        return [(int)$user->id, $sourceid, $targetid, (int)$course->id];
    }

    public function test_inactive_legacy_plan_can_be_selected_and_simulated_without_native_product(): void {
        global $DB;
        $this->resetAfterTest();
        [$userid, $sourceid, $targetid, $courseid] = $this->fixture();
        $target = (new CommerceBulkLegacyPlanGrantService($DB))->describe($targetid);
        $this->assertSame('3months', $target['durationkey']);
        $this->assertSame([$courseid], $target['courses']);
        $this->assertFalse($target['isactive']);
        $dryrun = (new CommerceBulkGrantDryRunService($DB))->simulate(
            CommerceBulkGrantDryRunService::SOURCE_LEGACY_PLAN,
            $sourceid, $targetid, $userid, null,
            CommerceBulkLegacyPlanGrantService::TARGET_LEGACY_PLAN
        );
        $this->assertSame(1, $dryrun['summary']['eligible']);
        $this->assertSame('legacy_plan', $dryrun['target']['type']);
        $this->assertSame(0, $DB->count_records('user_subscription', ['planid' => $targetid]));
    }

    public function test_snapshot_and_grant_record_exact_subscription_for_future_conversion(): void {
        global $DB;
        $this->resetAfterTest();
        [$userid, $sourceid, $targetid, $courseid] = $this->fixture();
        $service = new CommerceBulkGrantCampaignService($DB);
        $campaignid = $service->create_snapshot(
            'A2 courtesy access', CommerceBulkGrantDryRunService::SOURCE_LEGACY_PLAN,
            $sourceid, $targetid, [$userid], $userid,
            'gift', true, 0, [], null,
            CommerceBulkLegacyPlanGrantService::TARGET_LEGACY_PLAN
        );
        $campaign = $service->get_campaign($campaignid);
        $this->assertSame('legacy_plan', $campaign->targettype);
        $this->assertNull($campaign->targetproductid);
        $this->assertEquals($targetid, $campaign->targetplanid);
        $this->assertEquals(0, $campaign->sendemail);
        $this->assertSame('ready', $campaign->status);
        $stats = $service->run_now($campaignid, $userid);
        $this->assertSame(1, $stats['completed']);
        $member = $service->members($campaignid)[0];
        $audit = json_decode((string)$member->legacyresultjson, true);
        $this->assertSame('completed', $audit['state']);
        $this->assertSame([$courseid], $audit['courses']);
        $subscription = $DB->get_record('user_subscription', ['id' => $audit['subscriptionid']], '*', MUST_EXIST);
        $this->assertEquals($targetid, $subscription->planid);
        $this->assertEquals($audit['end'], $subscription->end_date);
        $this->assertEquals(0, $subscription->pricepaid);
        $this->assertSame(0, $DB->count_records('local_subs_commerce_grant'));
        $this->assertSame(0, $DB->count_records('local_subscriptions_commerce_purchase'));
        // Retrying the exact command never creates another plan subscription.
        $again = (new CommerceBulkLegacyPlanGrantService($DB))->grant(
            $userid, $targetid, $campaignid, (int)$member->id,
            json_decode((string)$campaign->targetjson, true)
        );
        $this->assertSame($audit['subscriptionid'], $again['subscriptionid']);
        $this->assertSame(1, $DB->count_records('user_subscription', ['userid' => $userid, 'planid' => $targetid]));
    }

    public function test_existing_target_plan_is_skipped_without_changing_end_date(): void {
        global $DB;
        $this->resetAfterTest();
        [$userid, $sourceid, $targetid] = $this->fixture();
        $end = time() + (365 * DAYSECS) * 2;
        $id = (int)$DB->insert_record('user_subscription', (object)[
            'userid' => $userid, 'planid' => $targetid,
            'payment_provider' => 'manual', 'transactionid' => 'existing-long',
            'start_date' => time() - 100, 'end_date' => $end,
            'pricepaid' => 0, 'currency' => 'EUR', 'status' => 'active',
            'creation_date' => time(), 'last_update' => time(),
        ]);
        $result = (new CommerceBulkGrantDryRunService($DB))->simulate(
            'legacy_plan', $sourceid, $targetid, $userid, null, 'legacy_plan'
        );
        $this->assertSame(1, $result['summary']['alreadyowned']);
        $this->assertSame($end, (int)$DB->get_field('user_subscription', 'end_date', ['id' => $id]));
    }

    public function test_plan_duration_change_after_snapshot_prevents_new_grant(): void {
        global $DB;
        $this->resetAfterTest();
        [$userid, $sourceid, $targetid] = $this->fixture();
        $service = new CommerceBulkGrantCampaignService($DB);
        $campaignid = $service->create_snapshot(
            'Legacy drift guard', 'legacy_plan', $sourceid, $targetid,
            [$userid], $userid, '', false, 0, [], null, 'legacy_plan'
        );
        $DB->set_field('subscription_plan', 'duration_key', '1year', ['id' => $targetid]);
        $stats = $service->run_now($campaignid, $userid);
        $this->assertSame(1, $stats['failed']);
        $this->assertSame(0, $DB->count_records('user_subscription', ['planid' => $targetid]));
    }
}
