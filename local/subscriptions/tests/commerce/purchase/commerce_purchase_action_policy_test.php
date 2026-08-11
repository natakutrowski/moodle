<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseFulfillmentSummary;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_purchase_action_policy_test extends advanced_testcase {
    public function test_retry_requires_paid_purchase_with_incomplete_fulfillment(): void {
        $policy = new CommercePurchaseActionPolicy();
        self::assertTrue($policy->can_retry_fulfillment($this->purchase('paid', 'failed')));
        self::assertFalse($policy->can_retry_fulfillment($this->purchase('failed', 'failed')));
        self::assertFalse($policy->can_retry_fulfillment($this->purchase('paid', 'completed')));
        self::assertFalse($policy->destructive_actions_available());
    }

    private function purchase(string $paymentstatus, string $fulfillmentstatus): CommercePurchaseDetails {
        $summary = new CommercePurchaseSummary(7, 'uuid', 'PUR-7', 'subscription', new CommercePurchaseCustomer(3, 'a@example.test', 'A', 'B'), ['Product'], 'EUR', 1200, 'paid', $paymentstatus, $fulfillmentstatus, 'stripe', 'native', time());
        return new CommercePurchaseDetails($summary, [], [], [new CommercePurchaseFulfillmentSummary('ref', 'course_access', $fulfillmentstatus, 'key')], null, null, []);
    }
}
