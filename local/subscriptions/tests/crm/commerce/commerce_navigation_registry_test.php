<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;

final class commerce_navigation_registry_test extends advanced_testcase {
    public function test_registry_has_stable_unique_ordered_items(): void {
        $this->resetAfterTest();
        $items = (new CommerceSectionNavigationRegistry())->all_items();
        $keys = array_map(static fn($item): string => $item->key, $items);
        $positions = array_map(static fn($item): int|float => $item->position, $items);

        $this->assertCount(9, $items);
        $this->assertSame([
            CommerceSectionNavigationRegistry::OVERVIEW,
            CommerceSectionNavigationRegistry::PRODUCTS,
            CommerceSectionNavigationRegistry::PURCHASES,
            CommerceSectionNavigationRegistry::MAIL,
            CommerceSectionNavigationRegistry::IDENTITIES,
            CommerceSectionNavigationRegistry::PERSONAL_OFFERS,
            CommerceSectionNavigationRegistry::GRANTS,
            CommerceSectionNavigationRegistry::STATISTICS,
            CommerceSectionNavigationRegistry::CONFIGURATION,
        ], $keys);
        $this->assertSame($keys, array_values(array_unique($keys)));
        $this->assertSame($positions, array_values(array_unique($positions)));
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
        foreach ($keys as $key) {
            $this->assertTrue(CommerceSectionNavigationRegistry::is_known($key));
        }
        $this->assertFalse(CommerceSectionNavigationRegistry::is_known('unknown'));
    }
}
