<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** 7.98D5 — Campus no longer owns Trial duration, triallimited, or account deletion. */
final class trial_legacy_cleanup_798d5_test extends \advanced_testcase {
    public function test_trial_gate_uses_native_subscription_end_date(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/trial_gate.php');
        self::assertStringNotContainsString("get_config('local_campus','trialdays')", $source);
        self::assertStringContainsString('$expires = (int)$trialsub->end_date;', $source);
        self::assertStringContainsString('souscription Native fournit aussi', $source);
    }

    public function test_triallimited_is_not_a_runtime_identity_anymore(): void {
        global $CFG;

        $lib = file_get_contents($CFG->dirroot . '/local/campus/lib.php');
        $observer = file_get_contents($CFG->dirroot . '/local/campus/classes/observers.php');
        self::assertStringContainsString("['shortname' => 'trialstudent']", $lib);
        self::assertStringNotContainsString("['shortname' => 'triallimited']", $lib);
        self::assertStringNotContainsString("'triallimited'", $observer);
    }

    public function test_legacy_task_cannot_suspend_or_delete_moodle_accounts(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/classes/task/trial_maint_task.php');
        self::assertStringContainsString('SubscriptionLifecycleService', $source);
        self::assertStringContainsString('Historical Campus-only rows are preserved as history', $source);
        self::assertStringNotContainsString('user_update_user(', $source);
        self::assertStringNotContainsString('delete_user(', $source);
        self::assertStringNotContainsString('destroy_user_sessions', $source);
        self::assertStringNotContainsString('trial_suspend_after', $source);
        self::assertStringNotContainsString('trial_delete_after', $source);
    }

    public function test_obsolete_trial_admin_controls_are_no_longer_exposed(): void {
        global $CFG;

        $settings = file_get_contents($CFG->dirroot . '/local/campus/settings.php');
        self::assertStringNotContainsString("'local_campus/trialdays'", $settings);
        self::assertStringNotContainsString("'local_campus/trialrole'", $settings);
        self::assertStringNotContainsString("'local_campus/trial_suspend_after_days'", $settings);
        self::assertStringNotContainsString("'local_campus/trial_delete_after_days'", $settings);
        self::assertStringContainsString('local_campus_trial_native_notice', $settings);
    }
}
