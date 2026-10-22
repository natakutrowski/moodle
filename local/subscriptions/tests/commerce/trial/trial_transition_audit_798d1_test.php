<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial\TrialLegacyTransitionAuditService;
use local_subscriptions\trial\TrialLegacyTransitionPolicy;

/** 7.98D1 — read-only cross-plugin Trial inventory. */
final class trial_transition_audit_798d1_test extends \advanced_testcase {
    public function test_audit_exposes_transition_duration_roles_and_consistency_without_writes(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        set_config('trial_plan_id', 0, 'local_subscriptions');
        set_config('trial_duration_days', 7, 'local_subscriptions');
        set_config('trialdays', 7, 'local_campus');

        $before = $DB->count_records('user_subscription');
        $policy = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => '2026-10-20 09:30',
            TrialLegacyTransitionPolicy::CONFIG_LEGACY_EXPIRE_AT => '2026-11-20 18:00',
        ], new \DateTimeZone('UTC'));
        $report = (new TrialLegacyTransitionAuditService($DB, $policy))->inspect(
            strtotime('2026-10-21 12:00:00 UTC')
        );

        self::assertSame($before, $DB->count_records('user_subscription'));
        self::assertSame(
            TrialLegacyTransitionPolicy::PHASE_CONVERSION_ONLY,
            $report['transition']['phase']
        );
        self::assertFalse($report['duration_configuration']['legacy_differs']);
        self::assertTrue($report['duration_configuration']['legacy_ignored']);
        self::assertArrayHasKey('campus_trial', $report);
        self::assertArrayHasKey('subscriptions', $report);
        self::assertArrayHasKey('trialstudent', $report['roles']);
        self::assertArrayHasKey('triallimited', $report['roles']);
        self::assertArrayHasKey('native_trialconversion', $report);
        self::assertArrayHasKey('consistency', $report);

        $settings = file_get_contents($CFG->dirroot . '/local/subscriptions/settings.php');
        $cli = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cli/commerce/audit/audit_798d_trial_transition.php'
        );
        self::assertIsString($settings);
        self::assertIsString($cli);
        self::assertStringContainsString('trial_registration_close_at', $settings);
        self::assertStringContainsString('trial_legacy_expire_at', $settings);
        self::assertStringContainsString('TrialLegacyTransitionAuditService', $cli);
    }

    public function test_audit_reports_legacy_duration_difference_without_making_it_authoritative(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('trial_plan_id', 0, 'local_subscriptions');
        set_config('trial_duration_days', 7, 'local_subscriptions');
        set_config('trialdays', 14, 'local_campus');

        $report = (new TrialLegacyTransitionAuditService(
            $DB,
            new TrialLegacyTransitionPolicy([], new \DateTimeZone('UTC'))
        ))->inspect(strtotime('2026-10-21 12:00:00 UTC'));

        self::assertTrue($report['duration_configuration']['legacy_differs']);
        self::assertTrue($report['duration_configuration']['legacy_ignored']);
        self::assertSame(14, $report['duration_configuration']['legacy_local_campus_trialdays']);
        self::assertSame(7, $report['duration_configuration']['authoritative_days']);
    }
}
