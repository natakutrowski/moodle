<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial\TrialLegacyTransitionPolicy;
use local_subscriptions\trial\TrialManualCreationGuard;

/** 7.98D6 — admin cannot bypass Date A by creating a Legacy Trial manually. */
final class trial_admin_creation_guard_798d6_test extends \advanced_testcase {
    public function test_trial_plan_is_allowed_before_date_a_and_blocked_at_date_a(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = 1700000000;
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Trial D6 guard',
            'accessscopeid' => null,
            'duration_key' => '1week',
            'is_active' => 1,
            'is_trial' => 1,
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        $policy = new TrialLegacyTransitionPolicy([
            'trial_registration_close_at' => (string)($now + HOURSECS),
            'trial_legacy_expire_at' => (string)($now + 2 * HOURSECS),
        ]);
        $guard = new TrialManualCreationGuard($DB, $policy);
        self::assertFalse($guard->is_plan_blocked($planid, $now));
        self::assertTrue($guard->is_plan_blocked($planid, $now + HOURSECS));

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('trial_admin_creation_closed', 'local_subscriptions'));
        $guard->assert_plan_allowed($planid, $now + HOURSECS);
    }

    public function test_non_trial_plan_remains_manually_creatable_after_date_a(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = 1700000000;
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Paid D6 guard',
            'accessscopeid' => null,
            'duration_key' => '1month',
            'is_active' => 1,
            'is_trial' => 0,
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $policy = new TrialLegacyTransitionPolicy([
            'trial_registration_close_at' => (string)($now - HOURSECS),
            'trial_legacy_expire_at' => (string)($now + HOURSECS),
        ]);
        $guard = new TrialManualCreationGuard($DB, $policy);
        self::assertFalse($guard->is_plan_blocked($planid, $now));
        $guard->assert_plan_allowed($planid, $now);
        self::addToAssertionCount(1);
    }

    public function test_manual_subscription_page_wires_both_ui_filter_and_server_guard(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/subscriptions/add.php');
        self::assertStringContainsString('!$trialregistrationsopen && !empty($plan->is_trial)', $source);
        self::assertStringContainsString('$trialmanualguard->assert_plan_allowed($planid)', $source);
    }
}
