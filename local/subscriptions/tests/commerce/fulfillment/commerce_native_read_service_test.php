<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFactory;
use local_subscriptions\commerce\read\CommerceNativeReadFactory;
use local_subscriptions\commerce\read\CommerceNativeReadResult;

final class commerce_native_read_service_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_disabled_shadow_read_returns_legacy_snapshot(): void {
        $fixture = $this->create_subscription_fixture();
        set_config('commerce_native_read_shadow_enabled', 0, 'local_subscriptions');
        $result = CommerceNativeReadFactory::create()->read('subscription', $fixture->subscriptionid, 'phpunit');
        $this->assertSame(CommerceNativeReadResult::STATUS_DISABLED, $result->get_status());
        $this->assertNotNull($result->get_snapshot());
    }

    public function test_shadow_read_reports_equal_after_dual_write(): void {
        $fixture = $this->create_subscription_fixture();
        set_config('commerce_dual_write_enabled', 1, 'local_subscriptions');
        set_config('commerce_dual_write_strict', 1, 'local_subscriptions');
        CommerceDualWriteFactory::create()->synchronise('subscription', $fixture->subscriptionid, 'phpunit');
        set_config('commerce_native_read_shadow_enabled', 1, 'local_subscriptions');
        $result = CommerceNativeReadFactory::create()->read('subscription', $fixture->subscriptionid, 'phpunit');
        $this->assertSame(CommerceNativeReadResult::STATUS_EQUAL, $result->get_status());
        $this->assertNotNull($result->get_snapshot());
    }

    public function test_shadow_read_falls_back_to_legacy_when_native_is_missing(): void {
        $fixture = $this->create_subscription_fixture();

        set_config('commerce_native_read_shadow_enabled', 1, 'local_subscriptions');
        set_config('commerce_native_read_shadow_strict', 0, 'local_subscriptions');

        $result = CommerceNativeReadFactory::create()->read(
            'subscription',
            $fixture->subscriptionid,
            'phpunit'
        );

        $this->assertDebuggingCalled();

        $this->assertSame(
            CommerceNativeReadResult::STATUS_MISSING_NATIVE,
            $result->get_status()
        );

        $this->assertNotNull($result->get_snapshot());
        $this->assertTrue($result->has_issue());
    }

    private function create_subscription_fixture(): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'nativeread@example.com']);
        $now = time();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Native read scope', 'course_ids' => '[]', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Native read plan', 'access_scope_id' => $scopeid, 'duration_key' => '1year',
            'is_active' => 1, 'creation_date' => $now, 'last_update' => $now,
            'is_recurring' => 0, 'is_trial' => 0, 'expiry_reminder_enabled' => 1,
        ]);
        $subscriptionid = (int)$DB->insert_record('user_subscription', (object)[
            'userid' => $user->id, 'planid' => $planid, 'status' => 'active',
            'creation_date' => $now, 'last_update' => $now, 'start_date' => $now,
            'end_date' => $now + 86400, 'pricepaid' => 10, 'currency' => 'EUR',
            'payment_failed' => 0, 'discount_percent' => 0, 'discount_amount' => 0,
        ]);
        return (object)['subscriptionid' => $subscriptionid];
    }
}
