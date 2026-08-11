<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;

final class commerce_purchase_d11_polish_test extends advanced_testcase {
    public function test_navigation_contains_one_purchase_item(): void {
        $this->resetAfterTest();
        $items = (new CommerceSectionNavigationRegistry())->all_items();
        $keys = array_map(static fn($item): string => $item->key, $items);

        $this->assertSame(1, array_count_values($keys)[CommerceSectionNavigationRegistry::PURCHASES]);
    }

    public function test_customer_display_name_contains_firstname_and_lastname(): void {
        $customer = new CommercePurchaseCustomer(42, 'ada@example.test', 'Ada', 'Lovelace');
        $this->assertSame('Ada Lovelace', $customer->display_name());
    }

    public function test_type_badges_are_human_readable(): void {
        $this->resetAfterTest();

        $this->assertStringContainsString('Subscription', CommercePurchasePresentation::type_badge('subscription'));
        $this->assertStringContainsString('Digital product', CommercePurchasePresentation::type_badge('digital'));
        $this->assertStringContainsString('Bundle', CommercePurchasePresentation::type_badge('bundle'));
    }
}
