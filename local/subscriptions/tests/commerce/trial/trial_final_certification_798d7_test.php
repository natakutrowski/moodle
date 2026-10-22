<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial\TrialLegacyTransitionPolicy;
use local_subscriptions\trial\TrialManualCreationGuard;

/**
 * 7.98D7 — final cross-cutting certification invariants for Legacy Trial retirement.
 */
final class trial_final_certification_798d7_test extends \advanced_testcase {

    public function test_four_transition_boundaries_are_exact_and_gapless(): void {
        $a = 1800000000;
        $b = $a + 30 * DAYSECS;
        $policy = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => (string)$a,
            TrialLegacyTransitionPolicy::CONFIG_LEGACY_EXPIRE_AT => (string)$b,
        ], new \DateTimeZone('UTC'));

        self::assertTrue($policy->registrations_are_open($a - 1));
        self::assertTrue($policy->conversion_window_is_open($a - 1));
        self::assertSame(TrialLegacyTransitionPolicy::PHASE_REGISTRATION_OPEN, $policy->phase($a - 1));

        self::assertFalse($policy->registrations_are_open($a));
        self::assertTrue($policy->conversion_window_is_open($a));
        self::assertSame(TrialLegacyTransitionPolicy::PHASE_CONVERSION_ONLY, $policy->phase($a));

        self::assertFalse($policy->registrations_are_open($b - 1));
        self::assertTrue($policy->conversion_window_is_open($b - 1));
        self::assertSame(TrialLegacyTransitionPolicy::PHASE_CONVERSION_ONLY, $policy->phase($b - 1));

        self::assertFalse($policy->registrations_are_open($b));
        self::assertFalse($policy->conversion_window_is_open($b));
        self::assertTrue($policy->legacy_window_is_closed($b));
        self::assertSame(TrialLegacyTransitionPolicy::PHASE_LEGACY_CLOSED, $policy->phase($b));
    }

    public function test_after_date_a_only_trial_manual_creation_is_blocked(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = 1800000000;
        $trialplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'D7 Trial plan',
            'accessscopeid' => null,
            'duration_key' => '1week',
            'is_active' => 1,
            'is_trial' => 1,
            'creation_date' => $now - DAYSECS,
            'last_update' => $now - DAYSECS,
        ]);
        $paidplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'D7 Paid plan',
            'accessscopeid' => null,
            'duration_key' => '1month',
            'is_active' => 1,
            'is_trial' => 0,
            'creation_date' => $now - DAYSECS,
            'last_update' => $now - DAYSECS,
        ]);

        $policy = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => (string)($now - HOURSECS),
            TrialLegacyTransitionPolicy::CONFIG_LEGACY_EXPIRE_AT => (string)($now + DAYSECS),
        ], new \DateTimeZone('UTC'));
        $guard = new TrialManualCreationGuard($DB, $policy);

        self::assertTrue($guard->is_plan_blocked($trialplanid, $now));
        self::assertFalse($guard->is_plan_blocked($paidplanid, $now));
        $guard->assert_plan_allowed($paidplanid, $now);
        self::addToAssertionCount(1);
    }

    public function test_d7_runner_is_strict_cross_plugin_and_non_destructive(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/subscriptions/tools/run_798d_trial_certification.sh';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('--testsuite local_subscriptions_testsuite --filter 798d', $source);
        self::assertStringContainsString('--testsuite local_campus_testsuite --filter 798d', $source);
        self::assertStringContainsString('commerce_trial_conversion_bridge_test.php', $source);
        self::assertStringContainsString('commerce_trial_native_cart_discount_test.php', $source);
        self::assertStringContainsString('commerce_trial_conversion_completion_test.php', $source);
        self::assertStringContainsString('commerce_795g23_cart_operations_test.php', $source);
        self::assertStringContainsString('audit_798d_trial_transition.php --strict', $source);
        self::assertStringNotContainsString('expire_798d_legacy_trials.php --apply', $source);
        self::assertStringNotContainsString(' --apply', $source);
    }
}
