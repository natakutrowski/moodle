<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\purchase\contract\CommercePurchaseActionContract;
use local_subscriptions\commerce\purchase\contract\CommercePurchaseListContract;
use local_subscriptions\commerce\purchase\contract\CommercePurchaseViewContract;

final class commerce_purchase_contract_test extends \advanced_testcase {
    public function test_unified_contracts_are_complete_and_unique(): void {
        $this->assertContains('commercialstatus', CommercePurchaseListContract::fields());
        $this->assertContains('diagnostics', CommercePurchaseViewContract::sections());
        $this->assertContains('retry_fulfillment', CommercePurchaseActionContract::actions());
        $this->assertSame(CommercePurchaseListContract::fields(), array_unique(CommercePurchaseListContract::fields()));
    }
}
