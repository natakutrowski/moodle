<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\fulfillment\bridge\CommerceFulfillmentFeatureToggle;
use local_subscriptions\commerce\fulfillment\postaction\CommercePostFulfillmentAction;
use local_subscriptions\commerce\fulfillment\postaction\CommercePostFulfillmentActionResult;
use local_subscriptions\commerce\fulfillment\postaction\CommercePostFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentBatchResult;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;

/**
 * Tests for phase 7.93E post-payment services.
 */
final class commerce_fulfillment_post_payment_test
    extends advanced_testcase {

    public function test_feature_toggle_is_disabled_by_override(): void {
        $toggle = new CommerceFulfillmentFeatureToggle(false);

        $this->assertFalse($toggle->is_enabled());
    }

    public function test_post_action_failure_does_not_change_fulfillment(): void {
        $operation = new CommerceFulfillmentOperation(
            'purchase:item:1',
            'subscription_enrolment'
        );
        $context = CommerceFulfillmentContext::confirmed(
            'purchase',
            'stripe',
            'tx_1',
            1000,
            'EUR',
            time()
        );
        $fulfillmentresult = new CommerceFulfillmentResult(
            $operation,
            CommerceFulfillmentResult::STATUS_COMPLETED
        );
        $batch = new CommerceFulfillmentBatchResult(
            $context,
            [$fulfillmentresult]
        );

        $action = new class implements CommercePostFulfillmentAction {
            public function get_key(): string {
                return 'failing_email';
            }

            public function supports(
                CommerceFulfillmentResult $result
            ): bool {
                return true;
            }

            public function execute(
                CommerceFulfillmentResult $result,
                CommerceFulfillmentContext $context
            ): CommercePostFulfillmentActionResult {
                throw new \RuntimeException('Email failed.');
            }
        };

        $report = (new CommercePostFulfillmentCoordinator([$action]))
            ->execute($batch);

        $this->assertTrue($batch->is_successful());
        $this->assertTrue($report->has_failures());
        $this->assertSame(
            CommercePostFulfillmentActionResult::STATUS_FAILED,
            $report->get_results()[0]->get_status()
        );
    }
}
