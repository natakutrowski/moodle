<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityCertificationFinding;
use local_subscriptions\commerce\customer\certification\CommerceCustomerIdentityCertificationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_customer_identity_certification_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_unresolved_purchase_without_account_is_valid(): void {
        global $DB;

        $this->create_purchase('guest-only@example.com');

        $report = (new CommerceCustomerIdentityCertificationService($DB))->certify();

        $this->assertTrue($report->is_certified());
        $this->assertSame(1, $report->get_metrics()['purchases_unresolved']);
        $this->assertSame(1, $report->get_metrics()['unresolved_without_account']);
        $this->assertSame(0, $report->count(CommerceCustomerIdentityCertificationFinding::ERROR));
    }

    public function test_unique_match_is_warning_and_strict_blocker(): void {
        global $DB;

        $this->getDataGenerator()->create_user(['email' => 'matchable@example.com']);
        $this->create_purchase('matchable@example.com');

        $report = (new CommerceCustomerIdentityCertificationService($DB))->certify();

        $this->assertTrue($report->is_certified(false));
        $this->assertFalse($report->is_certified(true));
        $this->assertSame(1, $report->get_metrics()['unresolved_matchable']);
        $this->assertGreaterThanOrEqual(1, $report->count(CommerceCustomerIdentityCertificationFinding::WARNING));
    }

    public function test_partial_native_and_legacy_identity_are_blocking_errors(): void {
        global $DB;

        $email = 'partial@example.com';
        $user = $this->getDataGenerator()->create_user(['email' => $email]);
        $legacyid = $this->create_legacy_digital_purchase($email);
        $purchaseid = $this->create_purchase($email, (int)$user->id, $legacyid);
        $purchase = $DB->get_record(CommercePersistenceSchema::TABLE_PURCHASE, ['id' => $purchaseid], '*', MUST_EXIST);
        $this->create_grant((string)$purchase->reference, $email, null);
        $this->create_digital_access((string)$purchase->reference, $email, null);

        $report = (new CommerceCustomerIdentityCertificationService($DB))->certify();

        $this->assertFalse($report->is_certified());
        $this->assertSame(2, $report->get_metrics()['partial_children']);
        $this->assertSame(1, $report->get_metrics()['legacy_partial']);
        $this->assertGreaterThanOrEqual(1, $report->count(CommerceCustomerIdentityCertificationFinding::ERROR));
    }

    public function test_different_beneficiary_email_is_not_reported_as_conflict(): void {
        global $DB;

        $buyer = $this->getDataGenerator()->create_user(['email' => 'buyer@example.com']);
        $gift = $this->getDataGenerator()->create_user(['email' => 'gift@example.com']);
        $purchaseid = $this->create_purchase('buyer@example.com', (int)$buyer->id);
        $purchase = $DB->get_record(CommercePersistenceSchema::TABLE_PURCHASE, ['id' => $purchaseid], '*', MUST_EXIST);
        $this->create_grant((string)$purchase->reference, 'gift@example.com', (int)$gift->id);

        $report = (new CommerceCustomerIdentityCertificationService($DB))->certify();

        $this->assertSame(0, $report->get_metrics()['conflicting_children']);
        $this->assertSame(0, $report->get_metrics()['partial_children']);
    }

    public function test_ambiguous_identity_is_blocking_error(): void {
        global $DB;

        $first = $this->getDataGenerator()->create_user(['username' => 'ambiguous1', 'email' => 'first-amb@example.com']);
        $second = $this->getDataGenerator()->create_user(['username' => 'ambiguous2', 'email' => 'second-amb@example.com']);
        $DB->set_field('user', 'email', 'duplicate-cert@example.com', ['id' => $first->id]);
        $DB->set_field('user', 'email', 'duplicate-cert@example.com', ['id' => $second->id]);
        $this->create_purchase('duplicate-cert@example.com');

        $report = (new CommerceCustomerIdentityCertificationService($DB))->certify();

        $this->assertFalse($report->is_certified());
        $this->assertSame(1, $report->get_metrics()['unresolved_ambiguous']);
    }

    private function create_purchase(string $email, ?int $userid = null, ?int $legacyid = null): int {
        global $DB;
        $now = time();
        $uuid = bin2hex(random_bytes(16));
        $reference = 'PUR-CERT-' . substr(strtoupper($uuid), 0, 16);
        return (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => $uuid,
            'reference' => $reference,
            'type' => 'digital',
            'legacyfamily' => $legacyid !== null ? 'digital' : null,
            'legacyid' => $legacyid,
            'userid' => $userid,
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

    private function create_grant(string $purchasereference, string $email, ?int $userid): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'cert-grant-' . bin2hex(random_bytes(4)),
            'idempotencykey' => 'cert-grant-idem-' . bin2hex(random_bytes(4)),
            'purchasereference' => $purchasereference,
            'itemreference' => 'DIGITAL.CERT',
            'productsku' => 'DIGITAL.CERT',
            'type' => 'digital_download',
            'resourcekey' => 'cert.pdf',
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
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

    private function create_digital_access(string $purchasereference, string $email, ?int $userid): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_dig_access', (object)[
            'grantreference' => 'cert-access-' . bin2hex(random_bytes(4)),
            'idempotencykey' => 'cert-access-idem-' . bin2hex(random_bytes(4)),
            'purchasereference' => $purchasereference,
            'productsku' => 'DIGITAL.CERT',
            'resourcekey' => 'cert.pdf',
            'beneficiaryuserid' => $userid,
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

    private function create_legacy_digital_purchase(string $email): int {
        global $DB;
        $now = time();
        $productid = (int)$DB->insert_record('subscription_digital_product', (object)[
            'slug' => 'cert-legacy-' . bin2hex(random_bytes(4)),
            'name' => 'Certification legacy product',
            'filename' => 'cert.pdf',
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
