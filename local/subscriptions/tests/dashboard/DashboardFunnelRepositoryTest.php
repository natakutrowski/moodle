<?php

namespace local_subscriptions\tests\dashboard;

use advanced_testcase;
use local_subscriptions\dashboard\funnel\DashboardFunnelRepository;

/**
 * Tests for Dashboard Funnel repository.
 *
 * @covers \local_subscriptions\dashboard\funnel\DashboardFunnelRepository
 */
final class DashboardFunnelRepositoryTest extends advanced_testcase {

    /**
     * Create a subscription plan.
     */
    private function create_plan(
        bool $istrial
    ): int {
        global $DB;

        $now = time();

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Test scope ' . uniqid('', true),
                'course_ids' => '[]',
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        return (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Funnel plan ' . uniqid('', true),
                'access_scope_id' => $scopeid,
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
     * Create one user subscription.
     */
    private function create_subscription(
        int $userid,
        int $planid,
        int $creationdate,
        string $provider = 'manual'
    ): int {
        global $DB;

        return (int)$DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $userid,
                'planid' => $planid,
                'pricepaid' => 0,
                'currency' => 'EUR',
                'payment_provider' => $provider,
                'start_date' => $creationdate,
                'end_date' =>
                    $creationdate + (30 * DAYSECS),
                'status' => 'active',
                'creation_date' => $creationdate,
                'last_update' => $creationdate,
                'discount_percent' => 0,
                'discount_amount' => 0,
            ]
        );
    }

    /**
     * Create a subscription payment request.
     */
    private function create_payment(
        int $userid,
        int $planid,
        int $subscriptionid,
        int $paymentdate,
        string $status = 'paid',
        float $amount = 100.0
    ): int {
        global $DB;

        return (int)$DB->insert_record(
            'subscription_payment_request',
            (object)[
                'planid' => $planid,
                'userid' => $userid,
                'subscriptionid' => $subscriptionid,
                'currency' => 'EUR',
                'price' => $amount,
                'amount_minor' =>
                    (int)round($amount * 100),
                'payment_provider' => 'test',
                'status' => $status,
                'creation_date' => $paymentdate,
                'last_update' => $paymentdate,
                'payment_date' => $paymentdate,
                'operation' => 'purchase_new',
                'locked_list_price' => $amount,
                'locked_discount_percent' => 0,
                'locked_discount_amount' => 0,
                'locked_final_price' => $amount,
                'locked_at' => $paymentdate,
            ]
        );
    }

    /**
     * Create a digital product.
     */
    private function create_digital_product(): int {
        global $DB;

        $now = time();

        return (int)$DB->insert_record(
            'subscription_digital_product',
            (object)[
                'slug' => 'funnel-' . uniqid(),
                'name' => 'Funnel digital product',
                'filename' => 'test.pdf',
                'price_eur' => 20,
                'price_rub' => 0,
                'enabled' => 1,
                'creation_date' => $now,
                'last_update' => $now,
                'sortorder' => 0,
            ]
        );
    }

    /**
     * Create a digital payment request.
     */
    private function create_digital_payment(
        int $userid,
        int $productid,
        int $paymentdate,
        string $status = 'paid',
        float $amount = 20.0
    ): int {
        global $DB;

        return (int)$DB->insert_record(
            'subscription_digital_payment_request',
            (object)[
                'productid' => $productid,
                'userid' => $userid,
                'email' => 'buyer' . $userid . '@example.test',
                'currency' => 'EUR',
                'price' => $amount,
                'amount_minor' =>
                    (int)round($amount * 100),
                'payment_provider' => 'test',
                'status' => $status,
                'creation_date' => $paymentdate,
                'last_update' => $paymentdate,
                'payment_date' => $paymentdate,
                'locked_list_price' => $amount,
                'locked_discount_percent' => 0,
                'locked_discount_amount' => 0,
                'locked_final_price' => $amount,
                'locked_at' => $paymentdate,
            ]
        );
    }

    public function test_trial_users_are_distinct(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (60 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $trialplanid = $this->create_plan(true);

        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $start + 100,
            'trial'
        );

        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $start + 200,
            'trial'
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            1,
            $snapshot->trialusers
        );
    }

    public function test_only_first_trial_defines_cohort(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (60 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $trialplanid = $this->create_plan(true);

        /*
         * First trial happened before the selected period.
         */
        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $start - DAYSECS,
            'trial'
        );

        /*
         * A second trial row appears during the period.
         */
        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $start + 100,
            'trial'
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            0,
            $snapshot->trialusers
        );
    }

    public function test_payment_within_window_converts_trial(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (60 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();

        $trialplanid = $this->create_plan(true);
        $paidplanid = $this->create_plan(false);

        $trialdate = $start + 100;

        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $trialdate,
            'trial'
        );

        $paidsubscriptionid =
            $this->create_subscription(
                (int)$user->id,
                $paidplanid,
                $trialdate + (5 * DAYSECS)
            );

        $this->create_payment(
            (int)$user->id,
            $paidplanid,
            $paidsubscriptionid,
            $trialdate + (5 * DAYSECS)
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            1,
            $snapshot->trialusers
        );

        $this->assertSame(
            1,
            $snapshot->convertedtrialusers
        );

        $this->assertSame(
            1,
            $snapshot->maturetrialusers
        );

        $this->assertSame(
            1,
            $snapshot->convertedmaturetrialusers
        );

        $this->assertSame(
            100.0,
            $snapshot->mature_trial_conversion()
        );
    }

    public function test_payment_after_window_does_not_convert_trial(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (70 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();

        $trialplanid = $this->create_plan(true);
        $paidplanid = $this->create_plan(false);

        $trialdate = $start + 100;

        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $trialdate,
            'trial'
        );

        $paidsubscriptionid =
            $this->create_subscription(
                (int)$user->id,
                $paidplanid,
                $trialdate + (31 * DAYSECS)
            );

        $this->create_payment(
            (int)$user->id,
            $paidplanid,
            $paidsubscriptionid,
            $trialdate + (31 * DAYSECS)
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            1,
            $snapshot->trialusers
        );

        $this->assertSame(
            0,
            $snapshot->convertedtrialusers
        );

        $this->assertSame(
            0,
            $snapshot->convertedmaturetrialusers
        );
    }

    public function test_failed_and_free_payments_do_not_convert(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (60 * DAYSECS);
        $end = $start + DAYSECS;

        $trialplanid = $this->create_plan(true);
        $paidplanid = $this->create_plan(false);

        $faileduser =
            $this->getDataGenerator()->create_user();

        $freeuser =
            $this->getDataGenerator()->create_user();

        foreach (
            [
                [$faileduser, 'failed', 100.0],
                [$freeuser, 'paid', 0.0],
            ] as [$user, $status, $amount]
        ) {
            $trialdate = $start + 100;

            $this->create_subscription(
                (int)$user->id,
                $trialplanid,
                $trialdate,
                'trial'
            );

            $subscriptionid =
                $this->create_subscription(
                    (int)$user->id,
                    $paidplanid,
                    $trialdate + DAYSECS
                );

            $this->create_payment(
                (int)$user->id,
                $paidplanid,
                $subscriptionid,
                $trialdate + DAYSECS,
                $status,
                $amount
            );
        }

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            2,
            $snapshot->trialusers
        );

        $this->assertSame(
            0,
            $snapshot->convertedtrialusers
        );
    }

    public function test_recent_trial_is_pending(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (5 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $trialplanid = $this->create_plan(true);

        $this->create_subscription(
            (int)$user->id,
            $trialplanid,
            $start + 100,
            'trial'
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            1,
            $snapshot->trialusers
        );

        $this->assertSame(
            0,
            $snapshot->maturetrialusers
        );

        $this->assertSame(
            1,
            $snapshot->pendingtrialusers
        );

        $this->assertNull(
            $snapshot->mature_trial_conversion()
        );
    }

    public function test_new_customer_is_counted_only_once(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $paidplanid = $this->create_plan(false);

        $subscriptionid =
            $this->create_subscription(
                (int)$user->id,
                $paidplanid,
                $start + 100
            );

        $this->create_payment(
            (int)$user->id,
            $paidplanid,
            $subscriptionid,
            $start + 200
        );

        $this->create_payment(
            (int)$user->id,
            $paidplanid,
            $subscriptionid,
            $start + 300
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            1,
            $snapshot->newcustomers
        );
    }

    public function test_existing_customer_is_not_new_again(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $paidplanid = $this->create_plan(false);

        $subscriptionid =
            $this->create_subscription(
                (int)$user->id,
                $paidplanid,
                $start - DAYSECS
            );

        $this->create_payment(
            (int)$user->id,
            $paidplanid,
            $subscriptionid,
            $start - DAYSECS
        );

        $this->create_payment(
            (int)$user->id,
            $paidplanid,
            $subscriptionid,
            $start + 100
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            0,
            $snapshot->newcustomers
        );
    }

    public function test_digital_buyers_are_distinct(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $productid = $this->create_digital_product();

        $this->create_digital_payment(
            (int)$user->id,
            $productid,
            $start + 100
        );

        $this->create_digital_payment(
            (int)$user->id,
            $productid,
            $start + 200
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            1,
            $snapshot->digitalbuyers
        );
    }

    public function test_failed_digital_payment_is_excluded(): void {
        $this->resetAfterTest(true);

        $repository =
            new DashboardFunnelRepository();

        $now = time();
        $start = $now - (10 * DAYSECS);
        $end = $start + DAYSECS;

        $user = $this->getDataGenerator()->create_user();
        $productid = $this->create_digital_product();

        $this->create_digital_payment(
            (int)$user->id,
            $productid,
            $start + 100,
            'failed'
        );

        $snapshot = $repository->snapshot(
            $start,
            $end,
            30
        );

        $this->assertSame(
            0,
            $snapshot->digitalbuyers
        );
    }
}