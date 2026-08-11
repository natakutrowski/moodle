<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

/** @covers \local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService */
final class commerce_795i1_order_presentation_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_native_course_order_exposes_safe_customer_access(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $reference = $this->create_purchase((int)$user->id, 'course_access', 'course:' . $course->id, (int)$course->id);
        $service = new CommerceOrderPresentationService($DB, new CommercePurchaseReadRepository($DB));

        $order = $service->find_for_user($reference, (int)$user->id);

        $this->assertNotNull($order);
        $this->assertTrue($order->is_paid());
        $this->assertTrue($order->has_available_accesses());
        $this->assertStringContainsString('/courses/' . $course->id, $order->items[0]->accesses[0]->url);
        $this->assertStringNotContainsString('/course/view.php', $order->items[0]->accesses[0]->url);
        $this->assertStringNotContainsString('/local/subscriptions/order_access.php', $order->items[0]->accesses[0]->url);
        $this->assertSame('payment_confirmed', $order->timeline[1]->label);
    }

    public function test_customer_cannot_read_another_users_order(): void {
        global $DB;
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $reference = $this->create_purchase((int)$owner->id, 'course_access', 'course:17', 17);
        $service = new CommerceOrderPresentationService($DB, new CommercePurchaseReadRepository($DB));

        $this->expectException(CommerceOrderPresentationAccessDeniedException::class);
        $service->find_for_user($reference, (int)$other->id);
    }

    private function create_purchase(int $userid, string $granttype, string $resourcekey, int $courseid): string {
        global $DB;
        $now = time();
        $reference = 'cmp_' . substr(hash('sha256', uniqid('', true)), 0, 24);
        $purchaseid = $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => substr(hash('sha256', $reference), 0, 32), 'reference' => $reference, 'type' => 'course',
            'legacyfamily' => null, 'legacyid' => null, 'userid' => $userid, 'customeremail' => 'buyer@example.test',
            'status' => 'fulfilled', 'currency' => 'EUR', 'subtotalminor' => 1000, 'discountminor' => 0,
            'totalminor' => 1000, 'customerjson' => '{}', 'snapshotjson' => '{}', 'metadatajson' => '{}',
            'snapshotversion' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record(CommercePersistenceSchema::TABLE_ITEM, (object)[
            'purchaseid' => $purchaseid, 'position' => 0, 'itemtype' => 'course', 'itemreference' => 'item-1',
            'label' => 'Cours test', 'quantity' => 1, 'currency' => 'EUR', 'unitminor' => 1000,
            'grossminor' => 1000, 'discountminor' => 0, 'netminor' => 1000,
            'pricingjson' => '{}', 'fulfillmentjson' => '{}', 'metadatajson' => '{}',
        ]);
        $DB->insert_record(CommercePersistenceSchema::TABLE_PAYMENT, (object)[
            'purchaseid' => $purchaseid, 'paymentuuid' => substr(hash('sha256', 'pay-' . $reference), 0, 32), 'sequence' => 1,
            'reference' => 'pay-' . $reference, 'provider' => 'stripe', 'providerreference' => 'pi-test',
            'transactionid' => 'txn-test', 'status' => 'paid', 'currency' => 'EUR', 'amountminor' => 1000,
            'legacyrequestid' => null, 'metadatajson' => '{}', 'paidat' => $now + 1,
            'timecreated' => $now, 'timemodified' => $now + 1,
        ]);
        $grantreference = 'grant-' . substr($reference, -10);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $grantreference, 'idempotencykey' => 'idem-' . $grantreference,
            'purchasereference' => $reference, 'itemreference' => 'item-1', 'productsku' => 'COURSE.TEST',
            'type' => $granttype, 'resourcekey' => $resourcekey, 'quantity' => 1, 'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'buyer@example.test', 'validfrom' => $now, 'validuntil' => null, 'status' => 'active',
            'configurationjson' => json_encode(['courseid' => $courseid]), 'metadatajson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ful_state', (object)[
            'grantreference' => $grantreference, 'idempotencykey' => 'state-' . $grantreference,
            'granttype' => $granttype, 'handlerclass' => 'test', 'status' => 'completed', 'attempts' => 1,
            'lastexecutionreference' => 'exec-test', 'lastsource' => 'phpunit', 'lastactoruserid' => null,
            'lastpayloadjson' => '{}', 'lastmessage' => 'done', 'lasterrorclass' => null,
            'timecreated' => $now, 'timestarted' => $now, 'timecompleted' => $now + 2, 'timemodified' => $now + 2,
        ]);
        return $reference;
    }
}
