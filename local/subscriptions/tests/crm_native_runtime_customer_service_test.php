<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\RuntimeCrmCommerceCustomerService;

final class crm_native_runtime_customer_service_test extends advanced_testcase {
    public function test_default_mode_keeps_legacy_source(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $snapshot = (new RuntimeCrmCommerceCustomerService())
            ->build_snapshot(
                (int) $user->id,
                $user->email
            );

        $this->assertSame(
            'legacy_fallback',
            $snapshot->get_source()
        );

        $this->assertSame(
            0,
            $snapshot->get_purchase_count()
        );
    }

    public function test_native_crm_mode_reads_empty_native_customer_safely(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        set_config(
            'commerce_native_crm_reads_enabled',
            1,
            'local_subscriptions'
        );

        $snapshot = (new RuntimeCrmCommerceCustomerService())
            ->build_snapshot(
                (int) $user->id,
                $user->email
            );

        $this->assertSame(
            'native_runtime',
            $snapshot->get_source()
        );

        $this->assertSame(
            0,
            $snapshot->get_purchase_count()
        );
    }
}