<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial\TrialLegacyTransitionPolicy;

/** 7.98D1 — authoritative Legacy Trial transition calendar. */
final class trial_transition_policy_798d1_test extends \basic_testcase {
    public function test_empty_calendar_preserves_historical_open_behaviour(): void {
        $policy = new TrialLegacyTransitionPolicy([], new \DateTimeZone('UTC'));
        $at = strtotime('2026-10-15 12:00:00 UTC');

        self::assertTrue($policy->registrations_are_open($at));
        self::assertTrue($policy->conversion_window_is_open($at));
        self::assertFalse($policy->legacy_window_is_closed($at));
        self::assertSame(
            TrialLegacyTransitionPolicy::PHASE_REGISTRATION_OPEN,
            $policy->phase($at)
        );
        self::assertSame([], $policy->configuration_issues());
    }

    public function test_exact_boundaries_produce_three_expected_phases(): void {
        $policy = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => '2026-10-20 09:30',
            TrialLegacyTransitionPolicy::CONFIG_LEGACY_EXPIRE_AT => '2026-11-20 18:00',
        ], new \DateTimeZone('UTC'));

        $closeat = strtotime('2026-10-20 09:30:00 UTC');
        $expireat = strtotime('2026-11-20 18:00:00 UTC');

        self::assertTrue($policy->registrations_are_open($closeat - 1));
        self::assertSame(
            TrialLegacyTransitionPolicy::PHASE_REGISTRATION_OPEN,
            $policy->phase($closeat - 1)
        );

        self::assertFalse($policy->registrations_are_open($closeat));
        self::assertTrue($policy->conversion_window_is_open($closeat));
        self::assertSame(
            TrialLegacyTransitionPolicy::PHASE_CONVERSION_ONLY,
            $policy->phase($closeat)
        );

        self::assertTrue($policy->conversion_window_is_open($expireat - 1));
        self::assertFalse($policy->conversion_window_is_open($expireat));
        self::assertTrue($policy->legacy_window_is_closed($expireat));
        self::assertFalse($policy->registrations_are_open($expireat));
        self::assertSame(
            TrialLegacyTransitionPolicy::PHASE_LEGACY_CLOSED,
            $policy->phase($expireat)
        );
    }

    public function test_dates_are_interpreted_in_explicit_site_timezone(): void {
        $policy = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => '2026-10-25 10:00',
        ], new \DateTimeZone('Europe/Paris'));

        self::assertSame(
            (new \DateTimeImmutable('2026-10-25 10:00', new \DateTimeZone('Europe/Paris')))->getTimestamp(),
            $policy->registration_close_at()
        );
    }

    public function test_invalid_or_inverted_calendar_is_reported_without_guessing(): void {
        $invalid = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => 'not-a-date',
            TrialLegacyTransitionPolicy::CONFIG_LEGACY_EXPIRE_AT => '2026-11-20 18:00',
        ], new \DateTimeZone('UTC'));
        $codes = array_column($invalid->configuration_issues(), 'code');
        self::assertContains('invalid_registration_close_at', $codes);

        $inverted = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => '2026-11-20 18:00',
            TrialLegacyTransitionPolicy::CONFIG_LEGACY_EXPIRE_AT => '2026-11-20 17:59',
        ], new \DateTimeZone('UTC'));
        $codes = array_column($inverted->configuration_issues(), 'code');
        self::assertContains('legacy_expire_not_after_registration_close', $codes);
    }

    public function test_single_configured_boundary_is_reported_as_incomplete_calendar(): void {
        $policy = new TrialLegacyTransitionPolicy([
            TrialLegacyTransitionPolicy::CONFIG_REGISTRATION_CLOSE_AT => '2026-10-20 09:30',
        ], new \DateTimeZone('UTC'));

        $issues = $policy->configuration_issues();
        self::assertSame('warning', $issues[0]['severity']);
        self::assertSame('legacy_expire_at_missing', $issues[0]['code']);
    }
}
