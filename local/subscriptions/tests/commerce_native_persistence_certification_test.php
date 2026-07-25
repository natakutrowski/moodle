<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceNativePersistenceCertificationService;
use local_subscriptions\commerce\certification\CommercePersistenceCertificationResult;
use local_subscriptions\commerce\certification\CommercePersistenceSnapshotHasher;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseSourceRegistry;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\migration\LegacySubscriptionPurchaseSource;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

final class commerce_native_persistence_certification_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_certification_round_trip_can_cleanup_created_native_rows(): void {
        global $DB;
        $legacyid = $this->create_legacy_subscription_fixture();
        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $service = new CommerceNativePersistenceCertificationService(
            new CommerceLegacyPurchaseSourceRegistry([
                new LegacySubscriptionPurchaseSource($DB, new SubscriptionPurchaseRepository()),
            ]),
            new CommerceLegacySnapshotFactory(),
            $repository,
            new CommerceLegacyNativeComparator(),
            new CommercePersistenceSnapshotHasher()
        );

        $result = $service->certify_one('subscription', $legacyid, true);

        $this->assertSame(CommercePersistenceCertificationResult::STATUS_CERTIFIED, $result->get_status());
        $this->assertSame($result->get_expected_hash(), $result->get_actual_hash());
        $this->assertTrue($result->was_created_during_certification());
        $this->assertTrue($result->was_cleaned_up());
        $this->assertFalse($repository->exists_for_legacy_reference('subscription', $legacyid));
    }

    public function test_certification_keep_mode_leaves_a_certified_native_row(): void {
        global $DB;
        $legacyid = $this->create_legacy_subscription_fixture();
        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $service = new CommerceNativePersistenceCertificationService(
            new CommerceLegacyPurchaseSourceRegistry([
                new LegacySubscriptionPurchaseSource($DB, new SubscriptionPurchaseRepository()),
            ]),
            new CommerceLegacySnapshotFactory(),
            $repository,
            new CommerceLegacyNativeComparator(),
            new CommercePersistenceSnapshotHasher()
        );

        $result = $service->certify_one('subscription', $legacyid, false);

        $this->assertTrue($result->is_certified());
        $this->assertFalse($result->was_cleaned_up());
        $this->assertTrue($repository->exists_for_legacy_reference('subscription', $legacyid));
    }

    private function create_legacy_subscription_fixture(): int {
        global $DB;
        $now = time();
        $user = $this->getDataGenerator()->create_user(['email' => 'certification@example.com']);
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Certification scope',
            'course_ids' => '[]',
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Certification plan',
            'access_scope_id' => $scopeid,
            'duration_key' => '1year',
            'is_active' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'is_recurring' => 0,
            'is_trial' => 0,
            'expiry_reminder_enabled' => 1,
        ]);
        return (int)$DB->insert_record('user_subscription', (object)[
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
    }
}
