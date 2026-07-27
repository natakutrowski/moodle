<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\read\policy\CommerceReadPolicy;
use local_subscriptions\crm\commerce\I10cCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\NativeCrmCommerceCustomerService;

final class commerce_i10c_crm_read_service_test extends advanced_testcase {
    public function test_service_can_be_constructed_with_explicit_dependencies(): void {
        $service = new I10cCrmCommerceCustomerService(
            new CommerceReadPolicy(),
            new NativeCrmCommerceCustomerService(),
            new LegacyCrmCommerceCustomerService()
        );

        $this->assertInstanceOf(I10cCrmCommerceCustomerService::class, $service);
    }
}
