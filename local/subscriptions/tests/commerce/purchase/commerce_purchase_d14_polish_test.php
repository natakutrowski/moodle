<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_purchase_d14_polish_test extends advanced_testcase {
    public function test_failed_fulfillment_is_retryable_from_list(): void {
        $summary = new CommercePurchaseSummary(
            10,
            'uuid',
            'SALE-10',
            'subscription',
            new CommercePurchaseCustomer(2, 'user@example.test', 'Ada', 'Lovelace'),
            ['Course'],
            'EUR',
            12000,
            'to_fulfill',
            'paid',
            'failed',
            'stripe',
            'native',
            time()
        );

        self::assertTrue((new CommercePurchaseActionPolicy())->can_retry_summary($summary));
    }

    public function test_completed_fulfillment_is_not_retryable_from_list(): void {
        $summary = new CommercePurchaseSummary(
            11,
            'uuid-2',
            'SALE-11',
            'digital',
            new CommercePurchaseCustomer(3, 'user2@example.test', 'Grace', 'Hopper'),
            ['PDF'],
            'EUR',
            2500,
            'fulfilled',
            'paid',
            'completed',
            'stripe',
            'native',
            time()
        );

        self::assertFalse((new CommercePurchaseActionPolicy())->can_retry_summary($summary));
    }
}
