<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial\TrialDurationPolicy;

/** 7.98D5 — Native duration authority and Legacy runtime cleanup. */
final class trial_legacy_cleanup_798d5_test extends \advanced_testcase {
    public function test_native_duration_policy_is_authoritative_and_deterministic(): void {
        $seven = new TrialDurationPolicy(['trial_duration_days' => 7]);
        self::assertSame(7, $seven->configured_days());
        self::assertSame(1000 + 7 * DAYSECS, $seven->end_at(1000));

        $unlimited = new TrialDurationPolicy(['trial_duration_days' => 0]);
        self::assertSame(0, $unlimited->configured_days());
        self::assertGreaterThan(1000 + 99 * 365 * DAYSECS, $unlimited->end_at(1000));

        $invalid = new TrialDurationPolicy(['trial_duration_days' => -4]);
        self::assertSame(TrialDurationPolicy::DEFAULT_DAYS, $invalid->configured_days());
    }

    public function test_trial_manager_uses_duration_policy_not_legacy_campus_setting(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/trial_manager.php');
        self::assertStringContainsString('TrialDurationPolicy::create()->configured_days()', $source);
        self::assertStringContainsString('TrialDurationPolicy::create()->end_at($start)', $source);
        self::assertStringNotContainsString("get_config('local_campus', 'trialdays'", $source);
    }

    public function test_audit_treats_expired_campus_only_rows_as_history_not_runtime_gap(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('trial_plan_id', 987654, 'local_subscriptions');
        set_config('trial_duration_days', 7, 'local_subscriptions');
        set_config('trialdays', 14, 'local_campus');

        $user = $this->getDataGenerator()->create_user();
        $now = 1700000000;
        $DB->insert_record('local_campus_trial', (object)[
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'userid' => $user->id,
            'timecreated' => $now - 20 * DAYSECS,
            'expiresat' => $now - DAYSECS,
            'status' => 2,
        ]);

        $report = \local_subscriptions\trial\TrialLegacyTransitionAuditService::create()->inspect($now);
        self::assertSame('local_subscriptions', $report['duration_configuration']['authority_component']);
        self::assertSame(7, $report['duration_configuration']['authoritative_days']);
        self::assertSame(14, $report['duration_configuration']['legacy_local_campus_trialdays']);
        self::assertTrue($report['duration_configuration']['legacy_ignored']);
        self::assertSame(1, $report['consistency']['campus_users_without_trial_subscription']);
        self::assertSame(0, $report['consistency']['campus_active_users_without_trial_subscription']);
        self::assertSame(1, $report['consistency']['campus_historical_users_without_trial_subscription']);
    }
}
