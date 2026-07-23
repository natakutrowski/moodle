<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService;

/**
 * Tests the Legacy CRM Commerce revenue compatibility calculation.
 *
 * @covers \local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService
 */
final class legacy_crm_commerce_revenue_test
    extends advanced_testcase {

    public function test_latest_payment_request_amount_has_priority():
        void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()
            ->create_user();

        $scopeid = $DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Commerce revenue scope',
                'course_ids' => '[]',
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $planid = $DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Commerce revenue plan',
                'access_scope_id' => $scopeid,
                'duration_key' => '1month',
                'is_active' => 1,
                'is_recurring' => 0,
                'is_trial' => 0,
                'expiry_reminder_enabled' => 1,
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $subscriptionid = $DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $user->id,
                'planid' => $planid,

                // Historical value intentionally incomplete.
                'pricepaid' => 0,

                'currency' => 'EUR',
                'payment_provider' => 'stripe',
                'transactionid' => 'legacy-revenue-test',

                'start_date' => time(),
                'end_date' => time() + 30 * DAYSECS,

                'status' => 'active',
                'payment_failed' => 0,

                'discount_percent' => 0,
                'discount_amount' => 0,

                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $DB->insert_record(
            'subscription_payment_request',
            (object)[
                'subscriptionid' => $subscriptionid,
                'planid' => $planid,
                'userid' => $user->id,
                'email' => $user->email,

                'currency' => 'EUR',
                'price' => 170.00,
                'locked_final_price' => 170.00,
                'amount_minor' => 17000,

                'payment_provider' => 'stripe',
                'status' => 'completed',
                'transactionid' =>
                    'payment-request-revenue-test',

                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $service =
            new LegacyCrmCommerceCustomerService();

        $snapshot = $service->build_snapshot(
            (int)$user->id,
            $user->email
        );

        $this->assertSame(
            [
                'EUR' => 17000,
            ],
            $snapshot->get_revenue_by_currency()
        );
    }

    public function test_latest_request_replaces_older_request():
        void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()
            ->create_user();

        $scopeid = $DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Commerce latest request scope',
                'course_ids' => '[]',
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $planid = $DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Commerce latest request plan',
                'access_scope_id' => $scopeid,
                'duration_key' => '1month',
                'is_active' => 1,
                'is_recurring' => 0,
                'is_trial' => 0,
                'expiry_reminder_enabled' => 1,
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $subscriptionid = $DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $user->id,
                'planid' => $planid,
                'pricepaid' => 100.00,
                'currency' => 'EUR',
                'payment_provider' => 'stripe',
                'start_date' => time(),
                'end_date' => time() + 30 * DAYSECS,
                'status' => 'active',
                'payment_failed' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'creation_date' => time(),
                'last_update' => time(),
            ]
        );

        $commonrequest = [
            'subscriptionid' => $subscriptionid,
            'planid' => $planid,
            'userid' => $user->id,
            'email' => $user->email,
            'currency' => 'EUR',
            'payment_provider' => 'stripe',
            'creation_date' => time(),
            'last_update' => time(),
        ];

        $DB->insert_record(
            'subscription_payment_request',
            (object)array_merge(
                $commonrequest,
                [
                    'price' => 100.00,
                    'locked_final_price' => 100.00,
                    'amount_minor' => 10000,
                    'status' => 'completed',
                    'transactionid' => 'old-request',
                ]
            )
        );

        $DB->insert_record(
            'subscription_payment_request',
            (object)array_merge(
                $commonrequest,
                [
                    'price' => 170.00,
                    'locked_final_price' => 170.00,
                    'amount_minor' => 17000,
                    'status' => 'completed',
                    'transactionid' => 'latest-request',
                ]
            )
        );

        $service =
            new LegacyCrmCommerceCustomerService();

        $snapshot = $service->build_snapshot(
            (int)$user->id,
            $user->email
        );

        $this->assertSame(
            [
                'EUR' => 17000,
            ],
            $snapshot->get_revenue_by_currency()
        );
    }
}