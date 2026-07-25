<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFactory;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadFactory;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadMode;
use local_subscriptions\commerce\runtime\read\CommerceRuntimeReadResult;

final class commerce_runtime_read_service_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_legacy_mode_returns_legacy(): void {
        $id = $this->create_subscription_fixture();
        $result = CommerceRuntimeReadFactory::create(CommerceRuntimeReadMode::LEGACY, false)
            ->read('subscription', $id, 'phpunit');
        $this->assertSame(CommerceRuntimeReadResult::SOURCE_LEGACY, $result->get_source());
        $this->assertSame(CommerceRuntimeReadResult::STATUS_OK, $result->get_status());
    }

    public function test_shadow_mode_returns_legacy_and_compares_native(): void {
        $id = $this->create_subscription_fixture();
        set_config('commerce_dual_write_enabled', 1, 'local_subscriptions');
        set_config('commerce_dual_write_strict', 1, 'local_subscriptions');
        CommerceDualWriteFactory::create()->synchronise('subscription', $id, 'phpunit');
        $result = CommerceRuntimeReadFactory::create(CommerceRuntimeReadMode::SHADOW, false)
            ->read('subscription', $id, 'phpunit');
        $this->assertSame(CommerceRuntimeReadResult::SOURCE_LEGACY, $result->get_source());
        $this->assertSame(CommerceRuntimeReadResult::STATUS_OK, $result->get_status());
    }

    public function test_native_mode_returns_native(): void {
        $id = $this->create_subscription_fixture();
        set_config('commerce_dual_write_enabled', 1, 'local_subscriptions');
        set_config('commerce_dual_write_strict', 1, 'local_subscriptions');
        CommerceDualWriteFactory::create()->synchronise('subscription', $id, 'phpunit');
        $result = CommerceRuntimeReadFactory::create(CommerceRuntimeReadMode::NATIVE, false)
            ->read('subscription', $id, 'phpunit');
        $this->assertSame(CommerceRuntimeReadResult::SOURCE_NATIVE, $result->get_source());
        $this->assertSame(CommerceRuntimeReadResult::STATUS_OK, $result->get_status());
    }

    public function test_auto_mode_falls_back_to_legacy_when_native_is_missing(): void {
        $id = $this->create_subscription_fixture();
        $result = CommerceRuntimeReadFactory::create(CommerceRuntimeReadMode::AUTO, false)
            ->read('subscription', $id, 'phpunit');
        $this->assertDebuggingCalled();
        $this->assertSame(CommerceRuntimeReadResult::SOURCE_LEGACY, $result->get_source());
        $this->assertSame(CommerceRuntimeReadResult::STATUS_FALLBACK, $result->get_status());
        $this->assertTrue($result->used_fallback());
    }

    public function test_auto_mode_prefers_native(): void {
        $id = $this->create_subscription_fixture();
        set_config('commerce_dual_write_enabled', 1, 'local_subscriptions');
        set_config('commerce_dual_write_strict', 1, 'local_subscriptions');
        CommerceDualWriteFactory::create()->synchronise('subscription', $id, 'phpunit');
        $service = CommerceRuntimeReadFactory::create(CommerceRuntimeReadMode::AUTO, false);
        $result = $service->read('subscription', $id, 'phpunit');
        $this->assertSame(CommerceRuntimeReadResult::SOURCE_NATIVE, $result->get_source());
        $this->assertFalse($result->used_fallback());
        $this->assertSame(1, $service->get_metrics()->to_array()['native_reads']);
    }

    private function create_subscription_fixture(): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'runtimeread@example.com']);
        $now = time();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Runtime read scope', 'course_ids' => '[]', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Runtime read plan', 'access_scope_id' => $scopeid, 'duration_key' => '1year',
            'is_active' => 1, 'creation_date' => $now, 'last_update' => $now,
            'is_recurring' => 0, 'is_trial' => 0, 'expiry_reminder_enabled' => 1,
        ]);
        return (int)$DB->insert_record('user_subscription', (object)[
            'userid' => $user->id, 'planid' => $planid, 'status' => 'active',
            'creation_date' => $now, 'last_update' => $now, 'start_date' => $now,
            'end_date' => $now + 86400, 'pricepaid' => 10, 'currency' => 'EUR',
            'payment_failed' => 0, 'discount_percent' => 0, 'discount_amount' => 0,
        ]);
    }
}
