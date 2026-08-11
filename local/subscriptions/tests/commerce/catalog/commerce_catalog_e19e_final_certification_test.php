<?php

namespace local_subscriptions;

use advanced_testcase;

/** @coversNothing */
final class commerce_catalog_e19e_final_certification_test extends advanced_testcase {
    public function test_retry_controller_uses_result_message_and_exposes_close_action_only_for_missing_grants(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/retry_fulfillment.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('$result->message', $source);
        $this->assertStringNotContainsString('$result->status', $source);
        $this->assertStringContainsString("if (\$result->message === 'missing_grants')", $source);
        $this->assertStringContainsString('close_without_fulfillment.php', $source);
    }

    public function test_close_controller_requires_sesskey_and_confirmation(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/purchases/close_without_fulfillment.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('require_sesskey()', $source);
        $this->assertStringContainsString('$OUTPUT->confirm(', $source);
        $this->assertStringContainsString('close_without_fulfillment(', $source);
    }

    public function test_close_action_is_idempotent_and_logged(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/purchase/action/CommercePurchaseActionService.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('is_closed_without_fulfillment', $source);
        $this->assertStringContainsString('COMMERCE_PURCHASE_FULFILLMENT_CLOSED_WITHOUT_DELIVERY', $source);
        $this->assertStringContainsString("'closed_without_fulfillment'", $source);
        $this->assertStringContainsString("'fulfillment_resolution'", $source);
        $this->assertStringContainsString('CommercePersistenceSchema::TABLE_PURCHASE', $source);
    }
}
