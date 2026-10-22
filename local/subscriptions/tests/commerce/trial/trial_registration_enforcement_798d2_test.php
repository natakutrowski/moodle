<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** 7.98D2 — Native Trial creation guard at Date A. */
final class trial_registration_enforcement_798d2_test extends \advanced_testcase {
    public function test_start_trial_refuses_new_trial_after_registration_close(): void {
        $this->resetAfterTest();

        set_config('trial_registration_close_at', (string)(time() - HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)(time() + DAYSECS), 'local_subscriptions');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('trial_registration_closed', 'local_subscriptions'));

        trial_manager::start_trial(123456789);
    }

    public function test_legacy_recovery_bypasses_date_a_but_not_normal_trial_validation(): void {
        $this->resetAfterTest();

        set_config('trial_registration_close_at', (string)(time() - HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)(time() + DAYSECS), 'local_subscriptions');
        set_config('trial_plan_id', 0, 'local_subscriptions');

        try {
            trial_manager::start_trial(123456789, true);
            self::fail('Legacy recovery must continue into the normal Trial validation path.');
        } catch (\moodle_exception $e) {
            self::assertSame('missing_trial_plan', $e->errorcode);
        }
    }


    public function test_legacy_recovery_does_not_bypass_global_legacy_expiry(): void {
        $this->resetAfterTest();

        set_config('trial_registration_close_at', (string)(time() - 2 * HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)(time() - HOURSECS), 'local_subscriptions');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('trial_registration_closed', 'local_subscriptions'));

        trial_manager::start_trial(123456789, true);
    }

    public function test_trial_manager_declares_explicit_legacy_recovery_escape_hatch(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/trial_manager.php');
        self::assertStringContainsString('bool $legacyrecovery = false', $source);
        self::assertStringContainsString('TrialLegacyTransitionPolicy::create()', $source);
        self::assertStringContainsString('conversion_window_is_open()', $source);
        self::assertStringContainsString("'trial_registration_closed'", $source);
    }
}
