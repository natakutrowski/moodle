<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Ensures list actions respect an administrative close without fulfillment. */
final class commerce_e20_closed_fulfillment_list_action_test extends \advanced_testcase {
    public function test_purchase_list_hides_retry_for_closed_purchases(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/purchases/index.php');
        $service = file_get_contents(
            $root . '/classes/commerce/purchase/action/CommercePurchaseActionService.php'
        );

        self::assertIsString($page);
        self::assertIsString($service);
        self::assertStringContainsString('closed_without_fulfillment_ids(', $page);
        self::assertStringContainsString('!isset($closedwithoutfulfillment[$purchase->id])', $page);
        self::assertStringContainsString(
            'function closed_without_fulfillment_ids(array $purchaseids)',
            $service
        );
    }
}
