<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** 7.98D4 — the Legacy Campus task must not suspend/delete accounts during the A→B grace or after B. */
final class trial_legacy_expiry_lifecycle_798d4_test extends \advanced_testcase {
    public function test_legacy_task_delegates_date_b_and_retires_account_lifecycle_after_date_a(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/classes/task/trial_maint_task.php');

        self::assertStringContainsString('TrialLegacyTransitionPolicy::create()', $source);
        self::assertStringContainsString('legacy_window_is_closed($now)', $source);
        self::assertStringContainsString('TrialLegacyExpiryService::create()', $source);
        self::assertStringContainsString('SubscriptionLifecycleService', $source);
        self::assertStringNotContainsString('user_update_user(', $source);
        self::assertStringNotContainsString('delete_user(', $source);
        self::assertStringNotContainsString('trial_suspend_after', $source);
        self::assertStringNotContainsString('trial_delete_after', $source);
    }
}
