<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListResult;

final class commerce_purchase_list_filter_test extends advanced_testcase {
    public function test_filter_normalises_query_without_mutating_other_values(): void {
        $filter = new CommercePurchaseListFilter('  customer@example.test  ', 'bundle', 'fulfilled', 'paid', 'fulfilled', 'stripe', 'eur');
        $this->assertSame('customer@example.test', $filter->normalized_query());
        $this->assertSame('bundle', $filter->type);
        $this->assertSame('fulfilled', $filter->commercialstatus);
    }

    public function test_result_exposes_pagination_contract(): void {
        $result = new CommercePurchaseListResult([], 42, 1, 25);
        $this->assertSame(42, $result->total);
        $this->assertSame(1, $result->page);
        $this->assertSame(25, $result->perpage);
    }
}
