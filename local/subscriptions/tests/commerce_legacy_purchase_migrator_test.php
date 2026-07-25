<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationResult;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseMigrator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseSourceRegistry;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\migration\LegacySubscriptionPurchaseSource;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

final class commerce_legacy_purchase_migrator_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_migrator_supports_dry_run_execute_and_idempotent_replay(): void {
        global $DB;
        $fixture = $this->create_legacy_subscription_fixture();
        $source = new LegacySubscriptionPurchaseSource($DB, new SubscriptionPurchaseRepository());
        $migrator = new CommerceLegacyPurchaseMigrator(
            new CommerceLegacyPurchaseSourceRegistry([$source]),
            new CommerceLegacySnapshotFactory(),
            CommercePurchaseSqlRepositoryFactory::create(),
            new CommerceLegacyNativeComparator()
        );

        $dryrun = $migrator->migrate_one('subscription', $fixture->subscriptionid, false);
        $this->assertSame(CommerceLegacyMigrationResult::STATUS_DRY_RUN, $dryrun->get_status());

        $migrated = $migrator->migrate_one('subscription', $fixture->subscriptionid, true);
        $this->assertSame(CommerceLegacyMigrationResult::STATUS_MIGRATED, $migrated->get_status());

        $replayed = $migrator->migrate_one('subscription', $fixture->subscriptionid, true);
        $this->assertSame(CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT, $replayed->get_status());
    }

    private function create_legacy_subscription_fixture(): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_user(['email' => 'migration@example.com']);
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Migration scope',
            'course_ids' => '[]',
            'creation_date' => time(),
            'last_update' => time(),
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Migration plan',
            'access_scope_id' => $scopeid,
            'duration_key' => '1year',
            'is_active' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        $subscriptionid = (int)$DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $planid,
            'status' => 'active',
            'creation_date' => time(),
            'last_update' => time(),
            'start_date' => time(),
            'end_date' => time() + 86400,
            'pricepaid' => 10,
            'currency' => 'EUR',
            'payment_failed' => 0,
            'discount_percent' => 0,
            'discount_amount' => 0,
        ]);
        return (object)['subscriptionid' => $subscriptionid];
    }
}