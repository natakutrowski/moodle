<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B9.5 Trial discount deadline regression coverage. */
final class trial_discount_deadline_j67b95_test
        extends \advanced_testcase {

    public function test_future_start_date_uses_creation_date(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Trial deadline scope',
                'course_ids' => '',
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        $planid = (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'accessscopeid' => $scopeid,
                'name' => 'Trial deadline plan',
                'duration_key' => '1week',
                'is_active' => 1,
                'is_trial' => 1,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        set_config('trial_plan_id', $planid, 'local_subscriptions');
        set_config('trial_discount_hours', 72, 'local_subscriptions');

        $created = $now - HOURSECS;

        $DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $user->id,
                'planid' => $planid,
                'pricepaid' => 0,
                'currency' => 'EUR',
                'transactionid' => 'future-start-regression',
                'payment_provider' => 'trial',
                'start_date' => $now + (2000 * DAYSECS),
                'end_date' => $now + (2100 * DAYSECS),
                'status' => 'ACTIVE',
                'creation_date' => $created,
            ]
        );

        $deadline = \local_subscriptions\trial_manager::
            discount_window_deadline((int)$user->id);

        $this->assertSame(
            $created + (72 * HOURSECS),
            $deadline
        );
        $this->assertLessThanOrEqual(
            $now + (72 * HOURSECS),
            $deadline
        );
    }
}
