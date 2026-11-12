<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommerceXpAutomaticRepairService as Auto;
use local_subscriptions\commerce\education\xp\CommerceXpAssistedRepairService as Assisted;

/** @covers \local_subscriptions\commerce\education\xp\CommerceXpAutomaticRepairService */
final class commerce_798_xp_automatic_repair_test extends \advanced_testcase {
    private function report(string $kind, int $proofat): array {
        $ob = (object)['kind' => $kind, 'status' => 'review', 'points' => 3,
            'reason' => $kind === 'personal'
                ? 'no_personal_completion_log' : 'no_promotion_source_ledger'];
        return ['eligible' => true, 'issues' => [], 'audit' => $ob, 'proofat' => $proofat,
            'proofid' => 123, 'badge' => 'available', 'existing' => 0,
            'sourcekey' => 'activity-v1:dummy', 'personal' => [(object)['id' => 101]]];
    }

    public function test_default_cron_is_off_and_live_mode_is_not_possible(): void {
        $this->resetAfterTest();
        unset_config('xp_auto_cron_enabled', 'local_subscriptions');
        unset_config('xp_auto_mode', 'local_subscriptions');
        $this->assertFalse(Auto::is_cron_enabled());
        $this->assertSame('simulation', Auto::mode());
        $this->assertFalse(Auto::is_live_enabled());
        $stats = Auto::create()->run();
        $this->assertSame('disabled', $stats['mode']);
        $this->assertSame(0, $stats['credited']);
    }

    public function test_simulation_never_credits_without_optin_live_mode(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('xp_auto_cron_enabled', 1, 'local_subscriptions');
        set_config('xp_auto_mode', 'simulation', 'local_subscriptions');
        $before = $DB->count_records('block_xp_logs');
        $promo = $DB->count_records('local_subs_commerce_ped_xp');
        $result = Auto::create($DB)->run();
        $this->assertSame('simulation', $result['mode']);
        $this->assertSame(0, $result['credited']);
        $this->assertSame($before, $DB->count_records('block_xp_logs'));
        $this->assertSame($promo, $DB->count_records('local_subs_commerce_ped_xp'));
    }

    public function test_policy_refuses_personal_outside_incident_and_invalid_proof(): void {
        $now = Auto::B1_END + 100000;
        $report = $this->report('personal', Auto::B1_START - 1);
        $this->assertFalse(Auto::classify($report, $now)['allowed']);
        $report['proofat'] = Auto::B1_END + 1;
        $this->assertFalse(Auto::classify($report, $now)['allowed']);
        $report['proofat'] = Auto::B1_START + 300;
        $this->assertTrue(Auto::classify($report, $now)['allowed']);
        $report['badge'] = 'earned';
        $this->assertFalse(Auto::classify($report, $now)['allowed']);
        $report['badge'] = 'available';
        $report['issues'] = ['ambiguous'];
        $this->assertFalse(Auto::classify($report, $now)['allowed']);
    }

    public function test_promotion_requires_original_personal_log(): void {
        $report = $this->report('promotion', Auto::B1_END + 200);
        $this->assertTrue(Auto::classify($report, $report['proofat'] + 3600)['allowed']);
        $report['personal'] = [];
        $this->assertFalse(Auto::classify($report, $report['proofat'] + 3600)['allowed']);
    }

    public function test_auto_award_impossible_even_when_mode_is_live_but_cron_off(): void {
        $this->resetAfterTest();
        set_config('xp_auto_cron_enabled', 0, 'local_subscriptions');
        set_config('xp_auto_mode', 'live', 'local_subscriptions');
        $this->assertFalse(Auto::is_live_enabled());
        $this->expectException(\RuntimeException::class);
        Assisted::create()->repair_automatically(123);
    }
}
