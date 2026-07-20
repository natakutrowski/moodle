<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\repositories\DashboardStatsRepository;

/**
 * Tests for Dashboard Stats repository.
 *
 * @covers \local_subscriptions\dashboard\repositories\DashboardStatsRepository
 */
final class DashboardStatsRepositoryTest extends advanced_testcase {
    /**
     * Create a minimal subscription plan.
     *
     * @param bool $istrial
     * @return int
     */
    private function create_plan(
        bool $istrial
    ): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Test plan ' . uniqid('', true),
                'accessscopeid' => 0,
                'duration_key' => '1month',
                'is_active' => 1,
                'is_recurring' => 0,
                'is_trial' => $istrial ? 1 : 0,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );
    }

    /**
     * Create a user subscription.
     *
     * @param int $userid
     * @param int $planid
     * @param int $creationdate
     * @return int
     */
    private function create_subscription(
        int $userid,
        int $planid,
        int $creationdate
    ): int {
        global $DB;

        return (int)$DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $userid,
                'planid' => $planid,
                'start_date' => $creationdate,
                'end_date' => $creationdate + DAYSECS,
                'status' => 'active',
                'creation_date' => $creationdate,
                'last_update' => $creationdate,
                'discount_percent' => 0,
                'discount_amount' => 0,
            ]
        );
    }

    /**
     * Create a successful subscription payment request.
     *
     * @param int $userid
     * @param int $planid
     * @param int $paymentdate
     * @param string $status
     * @return int
     */
    private function create_payment(
        int $userid,
        int $planid,
        int $paymentdate,
        string $status = 'paid',
        ?int $subscriptionid = null
    ): int {
        global $DB;

        if ($subscriptionid === null) {
            $subscriptionid = $this->create_subscription(
                $userid,
                $planid,
                $paymentdate
            );
        }        

        return (int)$DB->insert_record(
            'subscription_payment_request',
            (object)[
                'planid' => $planid,
                'userid' => $userid,
                'subscriptionid' => $subscriptionid,
                'currency' => 'EUR',
                'price' => 100,
                'amount_minor' => 10000,
                'payment_provider' => 'test',
                'status' => $status,
                'creation_date' => $paymentdate,
                'last_update' => $paymentdate,
                'payment_date' => $paymentdate,
                'operation' => 'purchase_new',
                'locked_list_price' => 100,
                'locked_discount_percent' => 0,
                'locked_discount_amount' => 0,
                'locked_final_price' => 100,
                'locked_at' => $paymentdate,
            ]
        );
    }

    public function test_count_new_trials_uses_plan_classification(): void {
        $this->resetAfterTest(true);

        $repository = new DashboardStatsRepository();

        $from = 1700000000;
        $to = $from + DAYSECS;

        $user = $this->getDataGenerator()->create_user();

        $trialplanid = $this->create_plan(true);
        $paidplanid = $this->create_plan(false);

        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $from + 100
        );

        $this->create_subscription(
            (int)$user->id,
            $paidplanid,
            $from + 200
        );

        $this->assertSame(
            1,
            $repository->count_new_trials(
                $from,
                $to
            )
        );
    }

    public function test_new_customer_is_counted_only_on_first_payment(): void {
        $this->resetAfterTest(true);

        $repository = new DashboardStatsRepository();

        $from = 1700000000;
        $to = $from + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $planid = $this->create_plan(false);

        $this->create_payment(
            (int)$user->id,
            $planid,
            $from + 100
        );

        $this->create_payment(
            (int)$user->id,
            $planid,
            $from + 200
        );

        $this->assertSame(
            1,
            $repository->count_new_customers(
                $from,
                $to
            )
        );
    }

    public function test_existing_customer_is_not_counted_again(): void {
        $this->resetAfterTest(true);

        $repository = new DashboardStatsRepository();

        $from = 1700000000;
        $to = $from + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $planid = $this->create_plan(false);

        /*
         * First historical payment before the selected period.
         */
        $this->create_payment(
            (int)$user->id,
            $planid,
            $from - DAYSECS
        );

        /*
         * Renewal or additional purchase during the selected period.
         */
        $this->create_payment(
            (int)$user->id,
            $planid,
            $from + 100
        );

        $this->assertSame(
            0,
            $repository->count_new_customers(
                $from,
                $to
            )
        );
    }

    public function test_failed_payment_is_not_a_new_customer(): void {
        $this->resetAfterTest(true);

        $repository = new DashboardStatsRepository();

        $from = 1700000000;
        $to = $from + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $planid = $this->create_plan(false);

        $this->create_payment(
            (int)$user->id,
            $planid,
            $from + 100,
            'failed'
        );

        $this->assertSame(
            0,
            $repository->count_new_customers(
                $from,
                $to
            )
        );
    }
}