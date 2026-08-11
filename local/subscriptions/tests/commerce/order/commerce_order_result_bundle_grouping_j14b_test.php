<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_order_result_bundle_grouping_j14b_test extends \advanced_testcase {
    public function test_bundle_accesses_are_grouped_by_component_product(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/order_result.php');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/order_result.css');

        self::assertStringContainsString('CommerceBundleComponentResolver', $source);
        self::assertStringContainsString('commerce-order-bundle-components', $source);
        self::assertStringContainsString("\$access->metadata['productsku']", $source);
        self::assertStringContainsString('.commerce-order-bundle-component', $css);
    }
}
