<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\recovery\CommerceCheckoutRecoveryService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_795h61_checkout_recovery_test extends advanced_testcase {
    public function test_paid_unfulfilled_purchase_produces_safe_repair_plan(): void {
        global $DB;
        $this->resetAfterTest(true);

        $purchaseid = $this->insert_purchase('rec-h61-paid', 'payment_pending');
        $this->insert_payment($purchaseid, 'paid');

        $diagnostic = CommerceCheckoutRecoveryService::create($DB)
            ->diagnose('rec-h61-paid');

        $this->assertTrue($diagnostic->is_found());
        $this->assertSame(['paid_purchase_not_fulfilled'], $diagnostic->get_issues());
        $this->assertSame(['complete_fulfillment'], $diagnostic->get_actions());
    }

    public function test_fulfilled_native_purchase_is_healthy_without_legacy_fulfillment_rows(): void {
        global $DB;
        $this->resetAfterTest(true);

        $reference = 'rec-h61-native';
        $purchaseid = $this->insert_purchase($reference, 'fulfilled');
        $this->insert_payment($purchaseid, 'paid');

        $now = time();
        $grantreference = 'grant-rec-h61-native';
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'grant-key-rec-h61-native',
            'purchasereference' => $reference,
            'itemreference' => 'COURSE.TEST',
            'productsku' => 'COURSE.TEST',
            'type' => 'pedagogical_promotion_join',
            'resourcekey' => 'promotion:4:course:17:product:28',
            'quantity' => 1,
            'beneficiaryuserid' => 2,
            'beneficiaryemail' => 'recovery@example.test',
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => '{}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ful_state', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'state-rec-h61-native',
            'granttype' => 'pedagogical_promotion_join',
            'handlerclass' => 'test',
            'status' => 'completed',
            'attempts' => 1,
            'lastexecutionreference' => 'exec-rec-h61-native',
            'lastsource' => 'phpunit',
            'lastactoruserid' => null,
            'lastpayloadjson' => '{}',
            'lastmessage' => 'done',
            'lasterrorclass' => null,
            'timecreated' => $now,
            'timestarted' => $now,
            'timecompleted' => $now + 1,
            'timemodified' => $now + 1,
        ]);

        $diagnostic = CommerceCheckoutRecoveryService::create($DB)
            ->diagnose($reference);

        $this->assertTrue($diagnostic->is_healthy());
        $this->assertSame([], $diagnostic->get_issues());
        $this->assertSame([], $diagnostic->get_actions());
    }

    public function test_unpaid_purchase_is_never_repairable(): void {
        global $DB;
        $this->resetAfterTest(true);

        $purchaseid = $this->insert_purchase('rec-h61-unpaid', 'payment_pending');
        $this->insert_payment($purchaseid, 'redirected');

        $diagnostic = CommerceCheckoutRecoveryService::create($DB)
            ->diagnose('rec-h61-unpaid');

        $this->assertSame(['payment_not_paid'], $diagnostic->get_issues());
        $this->assertSame([], $diagnostic->get_actions());
        $this->assertFalse($diagnostic->is_repairable());
    }

    private function insert_purchase(string $reference, string $status): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => substr(hash('sha256', $reference), 0, 32),
            'reference' => $reference,
            'type' => 'course_access',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => 'recovery@example.test',
            'status' => $status,
            'currency' => 'EUR',
            'subtotalminor' => 1000,
            'discountminor' => 0,
            'totalminor' => 1000,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function insert_payment(int $purchaseid, string $status): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PAYMENT, (object)[
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'provider' => 'stripe',
            'providerreference' => 'cs_test_recovery',
            'providerorderid' => null,
            'status' => $status,
            'currency' => 'EUR',
            'amountminor' => 1000,
            'transactionid' => $status === 'paid' ? 'pi_test_recovery' : null,
            'legacyrequestid' => null,
            'paidat' => $status === 'paid' ? $now : null,
            'metadatajson' => '{}',
            'paymenturl' => null,
            'providerpayload' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
