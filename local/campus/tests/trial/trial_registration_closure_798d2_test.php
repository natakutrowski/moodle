<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** 7.98D2 — Date A closes new Trial acquisition without breaking existing Trial access. */
final class trial_registration_closure_798d2_test extends \advanced_testcase {
    public function test_campus_helper_follows_authoritative_transition_policy(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/campus/lib.php');

        set_config('trial_registration_close_at', (string)(time() + HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)(time() + DAYSECS), 'local_subscriptions');
        self::assertTrue(local_campus_trial_registrations_are_open());

        set_config('trial_registration_close_at', (string)(time() - HOURSECS), 'local_subscriptions');
        self::assertFalse(local_campus_trial_registrations_are_open());
    }

    public function test_legacy_gate_blocks_only_first_time_creation_and_allows_existing_recovery(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/trial_gate.php');

        self::assertStringContainsString("'status' => 'registration_closed'", $source);
        self::assertStringContainsString('$registrationsopen = local_campus_trial_registrations_are_open($now)', $source);
        self::assertStringContainsString('!isloggedin() || isguestuser()', $source);
        self::assertStringContainsString('trial_manager::start_trial((int)$user->id, true)', $source);
        self::assertStringContainsString('trial_manager::start_trial((int)$user->id);', $source);
    }

    public function test_trial_check_and_course_ui_keep_existing_trial_access_but_hide_new_trial_cta(): void {
        global $CFG;

        $check = file_get_contents($CFG->dirroot . '/local/campus/trial_check.php');
        $course = file_get_contents($CFG->dirroot . '/local/campus/course.php');
        $popup = file_get_contents($CFG->dirroot . '/local/campus/amd/src/trial_popup.js');

        self::assertStringContainsString("'status' => 'registration_closed'", $check);
        self::assertStringContainsString('trial_manager::user_has_active_trial', $check);
        self::assertStringContainsString('($trialregistrationsopen || $istrial)', $course);
        self::assertStringContainsString("j.status === 'registration_closed'", $popup);
        self::assertStringContainsString('registrationsOpen', $popup);
    }

    public function test_modern_trial_identity_prefers_native_subscription_and_trialstudent(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/lib.php');

        self::assertStringContainsString('trial_manager::user_has_active_trial', $source);
        self::assertStringContainsString("['shortname' => 'trialstudent']", $source);
        self::assertStringNotContainsString("['shortname' => 'triallimited']", $source);
    }
}
