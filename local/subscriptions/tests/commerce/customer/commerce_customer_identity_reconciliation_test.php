<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_customer_identity_reconciliation_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_dry_run_matches_without_writing(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['email' => 'buyer@example.com']);
        $purchaseid = $this->create_purchase('buyer@example.com');

        $results = (new CommerceCustomerIdentityReconciliationService($DB))->reconcile_batch(0, false);

        $this->assertCount(1, $results);
        $this->assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED, $results[0]->status);
        $this->assertSame((int)$user->id, $results[0]->userid);
        $this->assertNull($DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $purchaseid]));
    }

    public function test_execute_reconciles_native_aggregate_and_legacy_source(): void {
        global $DB;

        $email = 'historic@example.com';
        $user = $this->getDataGenerator()->create_user(['email' => $email]);
        $legacyid = $this->create_legacy_digital_purchase($email);
        $purchaseid = $this->create_purchase($email, $legacyid);
        $purchase = $DB->get_record(CommercePersistenceSchema::TABLE_PURCHASE, ['id' => $purchaseid], '*', MUST_EXIST);
        $this->create_grant((string)$purchase->reference, $email);
        $this->create_digital_access((string)$purchase->reference, $email);
        $this->create_guest_session((string)$purchase->reference, $email);
        $foreignGrantId = $this->create_grant((string)$purchase->reference, 'gift@example.com', 'gift-grant');

        $results = (new CommerceCustomerIdentityReconciliationService($DB))->reconcile_batch(0, true);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED, $result->status);
        $this->assertSame(1, $result->grantsupdated);
        $this->assertSame(1, $result->digitalaccessupdated);
        $this->assertSame(1, $result->guestsessionsupdated);
        $this->assertSame(1, $result->legacyrecordsupdated);
        $this->assertEquals($user->id, $DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $purchaseid]));
        $this->assertEquals($user->id, $DB->get_field('local_subs_commerce_grant', 'beneficiaryuserid', ['purchasereference' => $purchase->reference, 'beneficiaryemail' => $email]));
        $this->assertEquals($user->id, $DB->get_field('local_subs_commerce_dig_access', 'beneficiaryuserid', ['purchasereference' => $purchase->reference]));
        $this->assertEquals($user->id, $DB->get_field('local_subs_commerce_guest', 'userid', ['purchasereference' => $purchase->reference]));
        $this->assertEquals($user->id, $DB->get_field('subscription_digital_payment_request', 'userid', ['id' => $legacyid]));
        $this->assertNull($DB->get_field('local_subs_commerce_grant', 'beneficiaryuserid', ['id' => $foreignGrantId]));
    }

    public function test_ambiguous_email_is_never_reconciled(): void {
        global $DB;

        $first = $this->getDataGenerator()->create_user(['username' => 'duplicate1', 'email' => 'first@example.com']);
        $second = $this->getDataGenerator()->create_user(['username' => 'duplicate2', 'email' => 'second@example.com']);
        $DB->set_field('user', 'email', 'duplicate@example.com', ['id' => $first->id]);
        $DB->set_field('user', 'email', 'duplicate@example.com', ['id' => $second->id]);
        $purchaseid = $this->create_purchase('duplicate@example.com');

        $results = (new CommerceCustomerIdentityReconciliationService($DB))->reconcile_batch(0, true);

        $this->assertCount(1, $results);
        $this->assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS, $results[0]->status);
        $this->assertCount(2, $results[0]->candidateuserids);
        $this->assertNull($DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $purchaseid]));
    }

    public function test_reconcile_user_is_idempotent(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['email' => 'idempotent@example.com']);
        $purchaseid = $this->create_purchase('idempotent@example.com');
        $service = new CommerceCustomerIdentityReconciliationService($DB);

        $first = $service->reconcile_user((int)$user->id, true);
        $second = $service->reconcile_user((int)$user->id, true);

        $this->assertCount(1, $first);
        $this->assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED, $first[0]->status);
        $this->assertSame([], $second);
        $this->assertEquals($user->id, $DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $purchaseid]));
    }

    public function test_user_creation_observer_reconciles_existing_guest_purchase(): void {
        global $DB;

        $purchaseid = $this->create_purchase('later@example.com');
        $user = $this->getDataGenerator()->create_user(['email' => 'later@example.com']);

        $this->assertEquals($user->id, $DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $purchaseid]));
    }

    public function test_single_purchase_reconciliation_does_not_touch_other_matches(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['email' => 'scoped@example.com']);
        $firstid = $this->create_purchase('scoped@example.com');
        $secondid = $this->create_purchase('scoped@example.com');
        $service = new CommerceCustomerIdentityReconciliationService($DB);

        $dryrun = $service->reconcile_purchase($firstid, false);
        $this->assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED, $dryrun->status);
        $this->assertNull($DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $firstid]));

        $result = $service->reconcile_purchase($firstid, true);

        $this->assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED, $result->status);
        $this->assertEquals($user->id, $DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $firstid]));
        $this->assertNull($DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'userid', ['id' => $secondid]));
    }

    private function create_purchase(string $email, ?int $legacyid = null): int {
        global $DB;
        $now = time();
        $uuid = bin2hex(random_bytes(16));
        $reference = 'PUR-' . substr(strtoupper($uuid), 0, 20);
        return (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => $uuid,
            'reference' => $reference,
            'type' => 'digital',
            'legacyfamily' => $legacyid !== null ? 'digital' : null,
            'legacyid' => $legacyid,
            'userid' => null,
            'customeremail' => $email,
            'status' => 'paid',
            'currency' => 'EUR',
            'subtotalminor' => 990,
            'discountminor' => 0,
            'totalminor' => 990,
            'customerjson' => json_encode(['email' => $email]),
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_grant(string $purchasereference, string $email, string $suffix = 'main'): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-' . $suffix . '-' . bin2hex(random_bytes(4)),
            'idempotencykey' => 'idem-grant-' . $suffix . '-' . bin2hex(random_bytes(4)),
            'purchasereference' => $purchasereference,
            'itemreference' => 'DIGITAL.TEST',
            'productsku' => 'DIGITAL.TEST',
            'type' => 'digital_download',
            'resourcekey' => 'test.pdf',
            'quantity' => 1,
            'beneficiaryuserid' => null,
            'beneficiaryemail' => $email,
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_digital_access(string $purchasereference, string $email): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_dig_access', (object)[
            'grantreference' => 'access-' . bin2hex(random_bytes(4)),
            'idempotencykey' => 'idem-access-' . bin2hex(random_bytes(4)),
            'purchasereference' => $purchasereference,
            'productsku' => 'DIGITAL.TEST',
            'resourcekey' => 'test.pdf',
            'beneficiaryuserid' => null,
            'beneficiaryemail' => $email,
            'downloadtoken' => bin2hex(random_bytes(32)),
            'maxdownloads' => null,
            'downloadcount' => 0,
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'lastdownloadat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_guest_session(string $purchasereference, string $email): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'guest-' . bin2hex(random_bytes(8)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'active',
            'currency' => 'EUR',
            'userid' => null,
            'email' => $email,
            'firstname' => 'Guest',
            'lastname' => 'Buyer',
            'purchasereference' => $purchasereference,
            'paymentreference' => null,
            'expiresat' => $now + DAYSECS,
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_legacy_digital_purchase(string $email): int {
        global $DB;
        $now = time();
        $productid = (int)$DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'legacy-' . bin2hex(random_bytes(4)),
            'name' => 'Legacy test product',
            'filename' => 'legacy.pdf',
            'price_eur' => 9.90,
            'price_rub' => 990,
            'enabled' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'sortorder' => 0,
        ]);
        return (int)$DB->insert_record('subscription_digital_payment_request', (object)[
            'productid' => $productid,
            'userid' => null,
            'email' => $email,
            'currency' => 'EUR',
            'price' => 9.90,
            'amount_minor' => 990,
            'payment_provider' => 'stripe',
            'status' => 'paid',
            'emailsent' => 1,
            'receipt_sent' => 1,
            'creation_date' => $now,
            'last_update' => $now,
            'attempts' => 1,
            'locked_list_price' => 9.90,
            'locked_discount_percent' => 0,
            'locked_discount_amount' => 0,
            'locked_final_price' => 9.90,
            'locked_at' => $now,
        ]);
    }
}
