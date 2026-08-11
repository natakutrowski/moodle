<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

/**
 * Certifies the Native CRM fulfillment read model introduced in 7.95 H4.5.
 *
 * @covers \local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository
 * @covers \local_subscriptions\commerce\purchase\readmodel\CommercePurchaseFulfillmentSummary
 * @covers \local_subscriptions\commerce\purchase\readmodel\CommercePurchaseFulfillmentAttemptSummary
 */
final class commerce_795h45_native_crm_read_model_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_details_are_built_from_native_grant_state_and_attempt_tables(): void {
        $reference = $this->create_purchase('fulfilled');
        $this->create_native_fulfillment($reference, 'completed');

        $details = $this->repository()->find_by_reference($reference);

        $this->assertNotNull($details);
        $this->assertCount(1, $details->grants);
        $this->assertCount(1, $details->fulfillments);
        $this->assertCount(1, $details->fulfillmentattempts);
        $this->assertSame('completed', $details->fulfillments[0]->status);
        $this->assertSame('course_access', $details->fulfillments[0]->key);
        $this->assertSame(2, $details->fulfillments[0]->duration());
        $this->assertSame('completed', $details->fulfillmentattempts[0]->status);
    }

    public function test_recent_purchase_summary_uses_native_fulfillment_state(): void {
        $reference = $this->create_purchase('fulfilled');
        $this->create_native_fulfillment($reference, 'completed');

        $summaries = $this->repository()->recent();

        $this->assertCount(1, $summaries);
        $this->assertSame('completed', $summaries[0]->fulfillmentstatus);
    }

    public function test_grant_without_state_is_exposed_as_planned(): void {
        $reference = $this->create_purchase('pending');
        $this->create_grant($reference);

        $details = $this->repository()->find_by_reference($reference);
        $summaries = $this->repository()->recent();

        $this->assertNotNull($details);
        $this->assertSame('planned', $details->fulfillments[0]->status);
        $this->assertSame('planned', $summaries[0]->fulfillmentstatus);
    }

    public function test_fulfillment_filter_queries_native_state_and_planned_grants(): void {
        $planned = $this->create_purchase('pending', 'planned');
        $this->create_grant($planned, 'grant-planned');

        $completed = $this->create_purchase('fulfilled', 'completed');
        $this->create_native_fulfillment($completed, 'completed', 'grant-completed');

        $plannedresult = $this->repository()->search(new CommercePurchaseListFilter(
            fulfillmentstatus: 'planned'
        ));
        $completedresult = $this->repository()->search(new CommercePurchaseListFilter(
            fulfillmentstatus: 'completed'
        ));

        $this->assertCount(1, $plannedresult->purchases);
        $this->assertSame($planned, $plannedresult->purchases[0]->reference);
        $this->assertCount(1, $completedresult->purchases);
        $this->assertSame($completed, $completedresult->purchases[0]->reference);
    }

    private function repository(): CommercePurchaseReadRepository {
        global $DB;
        return new CommercePurchaseReadRepository($DB);
    }

    private function create_purchase(string $status, string $suffix = 'main'): string {
        global $DB;
        $now = time();
        $reference = 'cmp_' . substr(md5($suffix), 0, 24);
        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => md5('uuid-' . $suffix),
            'reference' => $reference,
            'type' => 'course',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => 'test+' . $suffix . '@campusfr.fr',
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
        return $reference;
    }

    private function create_native_fulfillment(
        string $purchasereference,
        string $status,
        string $grantreference = 'grant-main'
    ): void {
        global $DB;
        $this->create_grant($purchasereference, $grantreference);
        $now = time();
        $DB->insert_record('local_subs_commerce_ful_state', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'state-' . $grantreference,
            'granttype' => 'course_access',
            'handlerclass' => '\\local_subscriptions\\tests\\fixtures\\CommerceEntitlementTestHandler',
            'status' => $status,
            'attempts' => 1,
            'lastexecutionreference' => 'exec-' . $grantreference,
            'lastsource' => 'phpunit',
            'lastactoruserid' => null,
            'lastpayloadjson' => '{"courseid":17}',
            'lastmessage' => 'Granted',
            'lasterrorclass' => null,
            'timecreated' => $now,
            'timestarted' => $now,
            'timecompleted' => $now + 2,
            'timemodified' => $now + 2,
        ]);
        $DB->insert_record('local_subs_commerce_ful_attempt', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'attempt-' . $grantreference,
            'executionreference' => 'exec-' . $grantreference,
            'granttype' => 'course_access',
            'handlerclass' => '\\local_subscriptions\\tests\\fixtures\\CommerceEntitlementTestHandler',
            'status' => 'completed',
            'dryrun' => 0,
            'source' => 'phpunit',
            'actoruserid' => null,
            'payloadjson' => '{"courseid":17}',
            'message' => 'Granted',
            'errorclass' => null,
            'timestarted' => $now,
            'timecompleted' => $now + 2,
        ]);
    }

    private function create_grant(string $purchasereference, string $grantreference = 'grant-main'): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'grant-key-' . $grantreference,
            'purchasereference' => $purchasereference,
            'itemreference' => 'item-course-a1',
            'productsku' => 'COURSE-A1',
            'type' => 'course_access',
            'resourcekey' => 'course:17',
            'quantity' => 1,
            'beneficiaryuserid' => null,
            'beneficiaryemail' => 'test@campusfr.fr',
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'planned',
            'configurationjson' => '{"courseid":17}',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
