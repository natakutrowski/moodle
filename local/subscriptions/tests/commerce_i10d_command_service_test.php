<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\command\dto\CommerceCommandRequest;
use local_subscriptions\commerce\command\service\CommercePurchaseCommandService;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFactory;

final class commerce_i10d_command_service_test extends advanced_testcase {
    public function test_disabled_command_returns_disabled_result(): void {
        global $DB;

        $this->resetAfterTest(true);
        set_config('commerce_dual_write_enabled', 0, 'local_subscriptions');

        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'I10D scope',
            'course_ids' => '[]',
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'I10D plan',
            'access_scope_id' => $scopeid,
            'duration_key' => '1year',
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        $subscriptionid = (int)$DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $planid,
            'status' => 'active',
            'creation_date' => $now,
            'last_update' => $now,
            'start_date' => $now,
            'end_date' => $now + DAYSECS,
            'pricepaid' => 10,
            'currency' => 'EUR',
            'payment_failed' => 0,
            'discount_percent' => 0,
            'discount_amount' => 0,
        ]);

        $service = new CommercePurchaseCommandService(
            CommerceDualWriteFactory::create()
        );
        $result = $service->synchronise(new CommerceCommandRequest(
            'subscription',
            $subscriptionid,
            'phpunit',
            'test'
        ));

        $this->assertSame('disabled', $result->get_status());
        $this->assertFalse($result->is_successful());
        $this->assertSame($subscriptionid, $result->get_legacy_id());
    }
}
