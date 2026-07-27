<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

final class commerce_entitlement_planner_test extends advanced_testcase {
    public function test_catalogue_definitions_become_customer_grants(): void {
        $item = new CommercePurchaseRequestItem(
            new CommerceItem('digital', 'DIGITAL.TEST', 'Test product', 2),
            2,
            1_000,
            'EUR',
            [
                'entitlements' => [[
                    'productsku' => 'DIGITAL.TEST',
                    'type' => 'digital_download',
                    'resourcekey' => 'digital:2',
                    'durationseconds' => 3_600,
                    'quantity' => 1,
                    'configuration' => ['downloads' => 5],
                    'sortorder' => 0,
                ]],
            ]
        );

        $request = new CommercePurchaseRequest(
            'purchase-test',
            new CommerceCustomer(42, 'student@example.com'),
            [$item]
        );

        $prepareditem = new CommercePreparedPurchaseItem(
            $item,
            'digital',
            'digital_download'
        );

        $preparation = new CommercePurchasePreparation(
            $request,
            [$prepareditem],
            CommercePurchaseValidationResult::valid(),
            1_700_000_000
        );

        $plan = (new CommerceEntitlementGrantPlanner())->plan(
            $preparation,
            1_700_000_100
        );

        $grant = $plan->get_grants()[0];

        $this->assertSame(1, $plan->count());
        $this->assertSame(2, $grant->get_quantity());
        $this->assertSame(1_700_003_700, $grant->get_valid_until());
        $this->assertSame(42, $grant->get_beneficiary_user_id());
        $this->assertSame(['downloads' => 5], $grant->get_configuration());
    }

    public function test_purchase_without_entitlement_definitions_creates_empty_plan(): void {
        $item = new CommercePurchaseRequestItem(
            new CommerceItem('service', 'SERVICE.TEST', 'Test service'),
            1,
            5_000,
            'EUR'
        );

        $request = new CommercePurchaseRequest(
            'purchase-empty',
            new CommerceCustomer(null, 'guest@example.com'),
            [$item]
        );

        $preparation = new CommercePurchasePreparation(
            $request,
            [new CommercePreparedPurchaseItem($item, 'service', 'service_delivery')],
            CommercePurchaseValidationResult::valid(),
            1_700_000_000
        );

        $plan = (new CommerceEntitlementGrantPlanner())->plan(
            $preparation,
            1_700_000_100
        );

        $this->assertTrue($plan->is_empty());
    }
}
