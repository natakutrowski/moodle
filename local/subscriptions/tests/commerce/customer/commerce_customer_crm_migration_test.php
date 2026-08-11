<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\crm\CommerceCustomerCrmAdapter;
use local_subscriptions\commerce\customer\crm\CommerceCustomerTimelineCollector;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerGrant;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerIdentity;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerMetrics;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPayment;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerPurchase;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerSnapshot;
use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerRepository;

final class commerce_customer_crm_migration_test extends \advanced_testcase {
    public function test_user360_adapter_and_timeline_use_unified_native_snapshot(): void {
        $this->resetAfterTest(true);
        $now = time();

        $payment = new CommerceCustomerPayment(
            1,
            10,
            0,
            'paid',
            'EUR',
            7000,
            'stripe',
            'provider-1',
            'tx-1',
            $now - 5,
            $now - 10,
            $now - 5
        );
        $grant = new CommerceCustomerGrant(
            1,
            'grant-1',
            'cmp-native',
            'SUB.PLAN.32',
            'SUB.PLAN.32',
            'course_access',
            'course:14:full',
            'active',
            42,
            'customer@example.test',
            $now - 4,
            null,
            ['courseid' => 14],
            ['operation' => 'upgrade']
        );
        $purchase = new CommerceCustomerPurchase(
            10,
            'uuid-native',
            'cmp-native',
            'CFR-2026-ABC123',
            'upgrade',
            'fulfilled',
            'EUR',
            7000,
            42,
            'customer@example.test',
            $now - 20,
            $now - 3,
            [[
                'label' => 'A2 Grammar → A2 Full',
                'type' => 'subscription',
                'metadata' => ['operation' => 'upgrade'],
            ]],
            [$payment],
            [$grant]
        );
        $metrics = new CommerceCustomerMetrics(
            1,
            1,
            1,
            1,
            1,
            0,
            ['upgrade' => 1],
            ['fulfilled' => 1],
            ['paid' => 1],
            ['stripe' => 1],
            ['course_access' => 1],
            ['EUR' => 7000],
            $now - 20,
            $now - 20,
            $now - 5
        );
        $snapshot = new CommerceCustomerSnapshot(
            new CommerceCustomerIdentity(42, 'customer@example.test', 'Nata', 'CampusFR'),
            [$purchase],
            [$payment],
            [$grant],
            $metrics
        );

        $adapter = new CommerceCustomerCrmAdapter();
        $rows = $adapter->purchase_rows($snapshot);
        $stats = $adapter->stats($snapshot, 1, $now, false);
        $events = (new CommerceCustomerTimelineCollector())->collect($snapshot);

        $this->assertCount(1, $rows);
        $this->assertSame('A2 Grammar → A2 Full', $rows[0]->label);
        $this->assertSame('CFR-2026-ABC123', $rows[0]->publicreference);
        $this->assertSame(1, $stats->purchasecount);
        $this->assertSame(1, $stats->upgradecount);
        $this->assertSame(1, $stats->activegrantcount);
        $this->assertSame('active_customer', $stats->crmstatus);
        $this->assertSame(
            ['commerce_purchase_upgrade', 'commerce_payment_paid', 'commerce_grant_course_access'],
            array_values(array_unique(array_map(
                static fn($event): string => $event->type,
                $events
            )))
        );
    }

    public function test_explorer_native_purchase_filter_merges_guest_email_and_user_orders(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user(['email' => 'explorer-native@example.test']);
        $now = time();

        $purchaseid = (int)$DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => 'explorer-native-uuid',
            'reference' => 'cmp-explorer-native',
            'type' => 'digital',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => $user->email,
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 490,
            'discountminor' => 0,
            'totalminor' => 490,
            'customerjson' => json_encode(['email' => $user->email], JSON_THROW_ON_ERROR),
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now - 10,
            'timemodified' => $now - 5,
        ]);
        $DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'provider' => 'stripe',
            'providerreference' => 'provider-explorer',
            'providerorderid' => null,
            'status' => 'paid',
            'currency' => 'EUR',
            'amountminor' => 490,
            'transactionid' => 'tx-explorer',
            'legacyrequestid' => null,
            'paidat' => $now - 5,
            'metadatajson' => '{}',
            'paymenturl' => null,
            'providerpayload' => null,
            'timecreated' => $now - 8,
            'timemodified' => $now - 5,
        ]);

        $criteria = new UserExplorerCriteria(
            query: $user->email,
            haspurchase: UserExplorerCriteria::PRESENCE_YES
        );
        $records = (new UserExplorerRepository())->get_records($criteria);

        $this->assertCount(1, $records);
        $this->assertSame((int)$user->id, (int)$records[0]->id);
        $this->assertSame(1, (int)$records[0]->purchasecount);
        $this->assertSame(1, (int)$records[0]->successfulpurchasecount);
        $this->assertSame(490, (int)$records[0]->revenueeurminor);
        $this->assertSame($now - 10, (int)$records[0]->lastpurchaseat);
    }
}
