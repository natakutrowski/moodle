<?php

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\certification\CommerceOrphanPaymentCleaner;

/**
 * @covers \local_subscriptions\commerce\certification\CommerceOrphanPaymentCleaner
 */
final class commerce_795f7e_orphan_payment_cleanup_test extends advanced_testcase {
    private const PAYMENT = 'local_subscriptions_commerce_payment';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_dry_run_finds_orphan_without_writing(): void {
        global $DB;
        $id = $DB->insert_record(self::PAYMENT, $this->payment_record(990001));

        $cleaner = new CommerceOrphanPaymentCleaner($DB);
        $orphans = $cleaner->inspect();

        $this->assertCount(1, $orphans);
        $this->assertSame($id, $orphans[0]['id']);
        $this->assertTrue($DB->record_exists(self::PAYMENT, ['id' => $id]));
    }

    public function test_execute_deletes_verified_orphan(): void {
        global $DB;
        $id = $DB->insert_record(self::PAYMENT, $this->payment_record(990002));

        $cleaner = new CommerceOrphanPaymentCleaner($DB);
        $result = $cleaner->execute($cleaner->inspect());

        $this->assertSame(1, $result['deleted']);
        $this->assertFalse($DB->record_exists(self::PAYMENT, ['id' => $id]));
    }

    private function payment_record(int $purchaseid): \stdClass {
        return (object)[
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'provider' => 'test',
            'providerreference' => 'test-' . $purchaseid,
            'status' => 'failed',
            'currency' => 'EUR',
            'amountminor' => 1000,
            'transactionid' => null,
            'legacyrequestid' => null,
            'paidat' => null,
            'metadatajson' => '{}',
        ];
    }
}
