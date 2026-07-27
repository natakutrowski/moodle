<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\read\service\CommerceReadModelMapper;

final class commerce_i10c_native_read_services_test extends advanced_testcase {
    public function test_mapper_builds_a_complete_purchase_view(): void {
        $purchase = (object)[
            'purchaseuuid' => str_repeat('a', 32),
            'reference' => 'PUR-I10C-001',
            'type' => 'subscription',
            'legacyfamily' => 'subscription',
            'legacyid' => 42,
            'userid' => 7,
            'customeremail' => 'student@example.com',
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 12000,
            'discountminor' => 2000,
            'totalminor' => 10000,
            'timecreated' => 100,
            'timemodified' => 200,
        ];
        $item = (object)[
            'position' => 0,
            'itemtype' => 'subscription',
            'itemreference' => 'plan:1',
            'label' => 'Plan A1',
            'quantity' => 1,
            'currency' => 'EUR',
            'unitminor' => 12000,
            'grossminor' => 12000,
            'discountminor' => 2000,
            'netminor' => 10000,
            'fulfillmentjson' => '{"courseids":[17]}',
            'metadatajson' => '{}',
        ];

        $view = (new CommerceReadModelMapper())->map_purchase($purchase, [$item], [], []);

        $this->assertSame('PUR-I10C-001', $view->reference);
        $this->assertSame(10000, $view->totalminor);
        $this->assertSame([17], $view->items[0]['fulfillment']['courseids']);
    }
}
