<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

/**
 * Certifies the J9A CRM purchase public-reference experience.
 *
 * @covers \local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository
 * @covers \local_subscriptions\commerce\order\reference\CommercePublicOrderReference
 */
final class commerce_purchase_j9a_public_reference_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_summary_exposes_public_reference_and_search_accepts_it(): void {
        global $DB;

        $timecreated = make_timestamp(2026, 4, 12, 10, 30, 0);
        $internalreference = 'cmp_j9a_public_reference_001';
        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => md5('j9a-public-reference'),
            'reference' => $internalreference,
            'type' => 'digital',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => 'j9a@example.test',
            'status' => 'paid',
            'currency' => 'EUR',
            'subtotalminor' => 4900,
            'discountminor' => 0,
            'totalminor' => 4900,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $timecreated,
            'timemodified' => $timecreated,
        ]);

        $expected = (new CommercePublicOrderReference())->from_internal(
            $internalreference,
            $timecreated
        );
        $repository = new CommercePurchaseReadRepository($DB);

        $recent = $repository->recent();
        self::assertCount(1, $recent);
        self::assertSame($expected, $recent[0]->publicreference);
        self::assertSame($internalreference, $recent[0]->reference);

        $result = $repository->search(new CommercePurchaseListFilter(query: $expected));
        self::assertCount(1, $result->purchases);
        self::assertSame($expected, $result->purchases[0]->publicreference);
    }

    public function test_crm_pages_use_public_reference_as_primary_label(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/purchases/index.php');
        $view = file_get_contents($root . '/admin/commerce/purchases/view.php');

        self::assertIsString($index);
        self::assertIsString($view);
        self::assertStringContainsString('$purchase->publicreference', $index);
        self::assertStringContainsString('commerce_purchase_internal_reference_short', $index);
        self::assertStringContainsString('commerce_purchase_open_order_details', $index);
        self::assertStringContainsString('$publicreference', $view);
        self::assertStringContainsString('commerce_purchase_internal_reference', $view);
        self::assertStringContainsString("'/local/subscriptions/order_details.php'", $view);
    }
}
