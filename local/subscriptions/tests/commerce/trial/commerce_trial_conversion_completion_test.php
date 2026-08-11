<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\trial\CommerceTrialConversionCompletionService;
use local_subscriptions\constants\Status;

/** @covers \local_subscriptions\commerce\trial\CommerceTrialConversionCompletionService */
final class commerce_trial_conversion_completion_test extends \advanced_testcase {
    public function test_trial_priced_purchase_preserves_trial_outside_purchased_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $a1 = $this->getDataGenerator()->create_course();
        $a2 = $this->getDataGenerator()->create_course();
        $now = time();

        $trialroleid = (int)$DB->get_field(
            'role',
            'id',
            ['shortname' => 'trialstudent'],
            IGNORE_MISSING
        );
        if ($trialroleid <= 0) {
            $trialroleid = create_role(
                'Trial student',
                'trialstudent',
                'Trial student role'
            );
        }

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Trial scope ' . $now,
                'course_ids' => $a1->id . ',' . $a2->id,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );
        $planid = (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Trial ' . $now,
                'accessscopeid' => $scopeid,
                'duration_key' => '1month',
                'is_active' => 1,
                'is_trial' => 1,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        $subscriptionid = (int)$DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $user->id,
                'planid' => $planid,
                'pricepaid' => 0,
                'currency' => 'EUR',
                'transactionid' => 'trial-preservation-test',
                'payment_provider' => 'trial',
                'start_date' => $now - DAYSECS,
                'end_date' => $now + DAYSECS,
                'status' => Status::ACTIVE,
                'creation_date' => $now,
                'last_update' => $now,
                'discount_percent' => 20,
                'discount_amount' => 0,
                'payment_failed' => 0,
            ]
        );

        $a1context = \context_course::instance($a1->id);
        $a2context = \context_course::instance($a2->id);
        role_assign($trialroleid, $user->id, $a1context->id);
        role_assign($trialroleid, $user->id, $a2context->id);

        $result = (new CommerceTrialConversionCompletionService($DB))->complete(
            (object)[
                'userid' => $user->id,
                'reference' => 'cmp_trial_partial',
            ],
            [(object)[
                'metadatajson' => json_encode([
                    'operation' => 'trialconversion',
                    'trialdiscountpercent' => 20,
                ]),
                'fulfillmentjson' => '{}',
            ]],
            $now
        );

        $subscription = $DB->get_record(
            'user_subscription',
            ['id' => $subscriptionid],
            '*',
            MUST_EXIST
        );

        $this->assertTrue($result->is_applicable());
        $this->assertSame(0, $result->get_subscriptions_replaced());
        $this->assertSame(0, $result->get_roles_removed());
        $this->assertSame(Status::ACTIVE, $subscription->status);
        $this->assertTrue(
            user_has_role_assignment(
                $user->id,
                $trialroleid,
                $a1context->id
            )
        );
        $this->assertTrue(
            user_has_role_assignment(
                $user->id,
                $trialroleid,
                $a2context->id
            )
        );
    }

    public function test_non_trial_purchase_does_not_consume_trial(): void {
        global $DB;

        $this->resetAfterTest(true);

        $result = (new CommerceTrialConversionCompletionService($DB))->complete(
            (object)['userid' => 123, 'reference' => 'cmp_regular'],
            [(object)[
                'metadatajson' => json_encode(['operation' => 'purchase']),
                'fulfillmentjson' => '{}',
            ]]
        );

        $this->assertFalse($result->is_applicable());
    }

    public function test_paid_completer_and_grant_planner_preserve_trial_contract(): void {
        global $CFG;

        $completer = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/fulfillment/native/checkout/' .
            'CommerceNativePaidPurchaseCompleter.php'
        );
        $planner = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/fulfillment/native/checkout/' .
            'CommerceNativePurchaseGrantPlanner.php'
        );

        $this->assertIsString($completer);
        $this->assertStringContainsString(
            'CommerceTrialConversionCompletionService',
            $completer
        );
        $this->assertIsString($planner);
        $this->assertStringContainsString(
            "'commerceoperation' => 'trialconversion'",
            $planner
        );
    }
}
