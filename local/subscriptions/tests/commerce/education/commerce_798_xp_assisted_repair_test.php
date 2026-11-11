<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommerceXpAssistedRepairService as Assisted;

/** @covers \local_subscriptions\commerce\education\xp\CommerceXpAssistedRepairService */
final class commerce_798_xp_assisted_repair_test extends \advanced_testcase {
    private function candidate(string $kind = 'personal'): object {
        global $DB;
        $id = $DB->insert_record('local_subs_xp_audit', (object)[
            'fingerprint' => hash('sha256', 'fixture'. $kind),
            'kind' => $kind, 'userid' => 98765, 'courseid' => 12345,
            'cmid' => 54321, 'ruleid' => 314, 'promotionid' => 0,
            'points' => 3, 'reason' => 'no_personal_completion_log',
            'status' => 'review', 'evidenceat' => time() - 600,
            'firstseen' => time(), 'lastseen' => time(),
            'resolvedat' => null, 'timemodified' => time(),
        ]);
        return $DB->get_record('local_subs_xp_audit', ['id' => $id], '*', MUST_EXIST);
    }

    public function test_keys_are_stable_and_specific_to_currency(): void {
        $a = (object)['kind' => 'personal', 'userid' => 15, 'cmid' => 200,
            'ruleid' => 30, 'promotionid' => 0];
        $b = clone $a;
        $this->assertSame(Assisted::request_key($a), Assisted::request_key($b));
        $b->kind = 'promotion';
        $b->promotionid = 2;
        $this->assertNotSame(Assisted::request_key($a), Assisted::request_key($b));
    }

    public function test_missing_module_is_blocked_and_never_credited(): void {
        global $DB;
        $this->resetAfterTest();
        $ob = $this->candidate();
        $personal = $DB->count_records('block_xp_logs');
        $promotion = $DB->count_records('local_subs_commerce_ped_xp');
        $report = Assisted::create($DB)->analyse((int)$ob->id);
        $this->assertFalse($report['eligible']);
        $this->assertNotEmpty($report['issues']);
        $this->assertSame(0, $report['proofat']);
        $this->assertSame($personal, $DB->count_records('block_xp_logs'));
        $this->assertSame($promotion, $DB->count_records('local_subs_commerce_ped_xp'));
    }

    public function test_grants_disabled_by_default_even_for_a_valid_post_token(): void {
        global $DB;
        $this->resetAfterTest();
        $ob = $this->candidate();
        unset_config('xp_assisted_enabled', 'local_subscriptions');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('disabled by configuration');
        Assisted::create($DB)->repair((int)$ob->id, str_repeat('a', 64), 2, 'Manual test explanation');
    }

    public function test_stale_or_unproven_candidate_refuses_grant_when_opted_in(): void {
        global $DB;
        $this->resetAfterTest();
        $ob = $this->candidate();
        set_config('xp_assisted_enabled', 1, 'local_subscriptions');
        $personal = $DB->count_records('block_xp_logs');
        $promotion = $DB->count_records('local_subs_commerce_ped_xp');
        try {
            Assisted::create($DB)->repair((int)$ob->id, str_repeat('a', 64),
                2, 'Manual test explanation');
            $this->fail('Unproven candidate should not be credited.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('non éligible', $e->getMessage());
        }
        $this->assertSame($personal, $DB->count_records('block_xp_logs'));
        $this->assertSame($promotion, $DB->count_records('local_subs_commerce_ped_xp'));
        $this->assertSame(0, $DB->count_records('local_subs_xp_audit_fix'));
    }

    public function test_preview_token_changes_when_status_or_balance_changes(): void {
        $ob = (object)['id' => 1, 'userid' => 99, 'cmid' => 123,
            'ruleid' => 10, 'promotionid' => 0, 'points' => 3, 'status' => 'review'];
        $report = ['audit' => $ob, 'kind' => 'personal', 'eligible' => true,
            'proofat' => 12000, 'proofid' => 7, 'badge' => 'available',
            'existing' => 0, 'xp' => 340];
        $token = Assisted::preview_token($report);
        $report['xp'] = 341;
        $this->assertNotSame($token, Assisted::preview_token($report));
        $report['xp'] = 340;
        $report['audit'] = clone $ob;
        $report['audit']->status = 'resolved';
        $this->assertNotSame($token, Assisted::preview_token($report));
    }
}
