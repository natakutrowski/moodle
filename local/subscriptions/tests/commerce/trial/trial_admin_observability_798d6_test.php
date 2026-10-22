<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\trial\TrialLegacyUserStateService;

/** 7.98D6 — per-user Trial lifecycle projection exposed to CRM. */
final class trial_admin_observability_798d6_test extends \advanced_testcase {
    public function test_active_expired_converted_and_historical_states_are_distinct(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = 1700000000;
        $planid = $this->make_trial_plan($now);
        set_config('trial_plan_id', $planid, 'local_subscriptions');
        set_config('trial_registration_close_at', (string)($now + DAYSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now + 2 * DAYSECS), 'local_subscriptions');

        $active = $this->getDataGenerator()->create_user();
        $this->insert_subscription((int)$active->id, $planid, 'active', $now - HOURSECS, $now + HOURSECS);
        $activeview = TrialLegacyUserStateService::create()->describe((int)$active->id, $now);
        self::assertSame(TrialLegacyUserStateService::STATE_ACTIVE, $activeview['state']);
        self::assertTrue($activeview['active']);

        $expired = $this->getDataGenerator()->create_user();
        $this->insert_subscription((int)$expired->id, $planid, 'expired', $now - 2 * DAYSECS, $now - DAYSECS);
        $expiredview = TrialLegacyUserStateService::create()->describe((int)$expired->id, $now);
        self::assertSame(TrialLegacyUserStateService::STATE_EXPIRED, $expiredview['state']);
        self::assertFalse($expiredview['active']);

        $converted = $this->getDataGenerator()->create_user();
        $this->insert_subscription((int)$converted->id, $planid, 'replaced', $now - 2 * DAYSECS, $now - DAYSECS);
        $convertedview = TrialLegacyUserStateService::create()->describe((int)$converted->id, $now);
        self::assertSame(TrialLegacyUserStateService::STATE_CONVERTED, $convertedview['state']);
        self::assertTrue($convertedview['converted']);

        $historical = $this->getDataGenerator()->create_user();
        $DB->insert_record('local_campus_trial', (object)[
            'email' => $historical->email,
            'firstname' => $historical->firstname,
            'lastname' => $historical->lastname,
            'userid' => $historical->id,
            'timecreated' => $now - 3 * DAYSECS,
            'expiresat' => $now - 2 * DAYSECS,
            'status' => 2,
        ]);
        $historicalview = TrialLegacyUserStateService::create()->describe((int)$historical->id, $now);
        self::assertSame(TrialLegacyUserStateService::STATE_LEGACY_HISTORICAL, $historicalview['state']);
        self::assertTrue($historicalview['historical_only']);
    }

    public function test_date_b_prevents_residual_active_record_from_being_presented_as_active_trial(): void {
        $this->resetAfterTest(true);
        $now = 1700000000;
        $planid = $this->make_trial_plan($now);
        set_config('trial_plan_id', $planid, 'local_subscriptions');
        set_config('trial_registration_close_at', (string)($now - 2 * HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now - HOURSECS), 'local_subscriptions');

        $user = $this->getDataGenerator()->create_user();
        $this->insert_subscription((int)$user->id, $planid, 'active', $now - DAYSECS, $now + DAYSECS);

        $view = TrialLegacyUserStateService::create()->describe((int)$user->id, $now);
        self::assertSame(TrialLegacyUserStateService::STATE_EXPIRED, $view['state']);
        self::assertFalse($view['active']);
        self::assertSame('legacy_closed', $view['transition_phase']);
    }

    private function make_trial_plan(int $now): int {
        global $DB;
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Trial D6 scope ' . $now,
            'course_ids' => '',
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        return (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Trial D6 ' . $now,
            'accessscopeid' => $scopeid,
            'duration_key' => '1week',
            'is_active' => 1,
            'is_trial' => 1,
            'creation_date' => $now,
            'last_update' => $now,
        ]);
    }

    private function insert_subscription(int $userid, int $planid, string $status, int $start, int $end): void {
        global $DB;
        $DB->insert_record('user_subscription', (object)[
            'userid' => $userid,
            'planid' => $planid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'transactionid' => 'trial-d6-' . $userid . '-' . $status,
            'payment_provider' => 'trial',
            'start_date' => $start,
            'end_date' => $end,
            'status' => $status,
            'creation_date' => $start,
            'last_update' => $start,
        ]);
    }
}
