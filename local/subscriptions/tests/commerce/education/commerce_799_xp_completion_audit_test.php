<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommerceXpAllModuleCompletionAudit as AuditV4;
use local_subscriptions\commerce\education\xp\CommerceXpAutomaticRepairService as Auto;

/** @covers \local_subscriptions\commerce\education\xp\CommerceXpAllModuleCompletionAudit */
final class commerce_799_xp_completion_audit_test extends \advanced_testcase {
    public function test_v4_audit_does_not_create_awards_without_completion(): void {
        global $DB;
        $this->resetAfterTest();
        $this->assertSame(0, $DB->count_records('local_subs_xp_audit'));
        $oldlevelup = $DB->count_records('block_xp_logs');
        $oldpromo = $DB->count_records('local_subs_commerce_ped_xp');
        $stats = AuditV4::create($DB)->run(time());
        $this->assertSame(0, $stats['candidates']);
        $this->assertSame($oldlevelup, $DB->count_records('block_xp_logs'));
        $this->assertSame($oldpromo, $DB->count_records('local_subs_commerce_ped_xp'));
    }

    public function test_site_admin_review_is_excluded_without_changing_awards(): void {
        global $DB;
        $this->resetAfterTest();
        $admins = get_admins();
        $this->assertNotEmpty($admins);
        $admin = reset($admins);
        $this->assertTrue(is_siteadmin((int)$admin->id));
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        foreach ([(int)$admin->id, (int)$user->id] as $uid) {
            $DB->insert_record('local_subs_xp_audit', (object)[
                'fingerprint' => hash('sha256', 'v41-admin-test-'.$uid),
                'kind' => 'personal', 'userid' => $uid, 'courseid' => 15,
                'cmid' => 6007, 'ruleid' => 313, 'promotionid' => 0,
                'points' => 10, 'reason' => 'v4_completion_review_only',
                'status' => 'review', 'evidenceat' => $now - 3600,
                'firstseen' => $now - 100, 'lastseen' => $now - 100,
                'resolvedat' => null, 'timemodified' => $now - 100,
            ]);
        }
        $beforexp = $DB->count_records('block_xp_logs');
        $beforepromo = $DB->count_records('local_subs_commerce_ped_xp');
        $audit = AuditV4::create($DB);
        $this->assertSame(1, $audit->exclude_site_admin_cases($now));
        $this->assertSame(0, $audit->exclude_site_admin_cases($now + 1));
        $this->assertSame('excluded', $DB->get_field('local_subs_xp_audit', 'status',
            ['userid' => (int)$admin->id]));
        $this->assertSame('excluded_site_admin:v4_completion_review_only', $DB->get_field('local_subs_xp_audit', 'reason',
            ['userid' => (int)$admin->id]));
        $this->assertSame('review', $DB->get_field('local_subs_xp_audit', 'status',
            ['userid' => (int)$user->id]));
        $this->assertSame($beforexp, $DB->count_records('block_xp_logs'));
        $this->assertSame($beforepromo, $DB->count_records('local_subs_commerce_ped_xp'));
    }

    public function test_activity_name_falls_back_safely_for_missing_module(): void {
        global $DB;
        $this->resetAfterTest();
        $this->assertSame('#3756',
            \local_subscriptions\commerce\education\xp\CommerceXpAssistedRepairService::activity_name(
                $DB, 'unknown_dummy_module', 98, 3756));
        $this->assertSame('#3756',
            \local_subscriptions\commerce\education\xp\CommerceXpAssistedRepairService::activity_name(
                $DB, '../../user', 98, 3756));
    }

    public function test_automatic_repair_refuses_v4_review_only_observation(): void {
        $o = (object)['id' => 5, 'kind' => 'personal', 'status' => 'review',
            'reason' => 'v4_completion_review_only', 'points' => 5];
        $report = ['audit' => $o, 'eligible' => true, 'issues' => [],
            'badge' => 'available', 'existing' => 0, 'proofid' => 5,
            'proofat' => time() - 3600];
        $decision = Auto::classify($report, time());
        $this->assertFalse($decision['allowed']);
        $this->assertStringContainsString('V4', $decision['reason']);
    }

    public function test_v4_non_h5p_findings_keep_v3_auto_guard(): void {
        $o = (object)['id' => 6, 'kind' => 'promotion', 'status' => 'review',
            'reason' => 'v4_promotion_review_only', 'points' => 5];
        $report = ['audit' => $o, 'eligible' => true, 'issues' => [],
            'badge' => 'available', 'existing' => 0, 'proofid' => 6,
            'proofat' => time() - 3600];
        $this->assertFalse(Auto::classify($report, time())['allowed']);
    }
}
