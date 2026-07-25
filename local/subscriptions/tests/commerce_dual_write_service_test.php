<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFactory;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteResult;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

final class commerce_dual_write_service_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_disabled_dual_write_does_not_create_native_purchase(): void {
        $fixture = $this->create_subscription_fixture();
        set_config('commerce_dual_write_enabled', 0, 'local_subscriptions');
        $result = CommerceDualWriteFactory::create()->synchronise('subscription', $fixture->subscriptionid, 'phpunit');
        $this->assertSame(CommerceDualWriteResult::STATUS_DISABLED, $result->get_status());
        $this->assertNull(CommercePurchaseSqlRepositoryFactory::create()->find_by_legacy_reference('subscription', $fixture->subscriptionid));
    }

    public function test_dual_write_creates_updates_and_is_idempotent(): void {
        global $DB;
        $fixture = $this->create_subscription_fixture();
        set_config('commerce_dual_write_enabled', 1, 'local_subscriptions');
        set_config('commerce_dual_write_strict', 1, 'local_subscriptions');
        $service = CommerceDualWriteFactory::create();

        $created = $service->synchronise('subscription', $fixture->subscriptionid, 'phpunit');
        $this->assertSame(CommerceDualWriteResult::STATUS_CREATED, $created->get_status());

        $unchanged = $service->synchronise('subscription', $fixture->subscriptionid, 'phpunit');
        $this->assertSame(CommerceDualWriteResult::STATUS_UNCHANGED, $unchanged->get_status());

        $DB->set_field('user_subscription', 'pricepaid', 25.50, ['id' => $fixture->subscriptionid]);
        $updated = $service->synchronise('subscription', $fixture->subscriptionid, 'phpunit');
        $this->assertSame(CommerceDualWriteResult::STATUS_UPDATED, $updated->get_status());
    }

    private function create_subscription_fixture(): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'dualwrite@example.com']);
        $now = time();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Dual write scope', 'course_ids' => '[]', 'creation_date' => $now, 'last_update' => $now,
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Dual write plan', 'access_scope_id' => $scopeid, 'duration_key' => '1year',
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
