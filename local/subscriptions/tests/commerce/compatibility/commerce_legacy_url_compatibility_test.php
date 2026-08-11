<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\compatibility\CommerceLegacyUrlCompatibilityService;

final class commerce_legacy_url_compatibility_test extends \advanced_testcase {
    public function test_route_policy_classifies_historical_customer_routes(): void {
        $policy = CommerceLegacyUrlCompatibilityService::route_policy();
        $this->assertCount(8, $policy);
        $this->assertSame('redirect_mapped_product_to_native_storefront', $policy['digital_product.php']);
        $this->assertSame('legacy_adapter_retained_for_historical_operations', $policy['checkout.php']);
    }
}
