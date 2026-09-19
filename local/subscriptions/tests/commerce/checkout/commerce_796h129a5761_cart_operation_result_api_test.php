<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5761_cart_operation_result_api_test extends \advanced_testcase {
    public function test_reconciliation_uses_actual_cart_operation_result_api(): void {
        global $CFG;
        $service = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceAuthenticatedCartReconciliationService.php');
        $result = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/cart/domain/CommerceCartOperationResult.php');
        self::assertStringContainsString('public function has_changed(): bool', $result);
        self::assertStringContainsString('$result->has_changed()', $service);
        self::assertStringNotContainsString('$result->is_changed()', $service);
    }
}
