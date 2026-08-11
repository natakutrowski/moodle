<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Regression coverage for H4.9 multi-item and bundle fulfillment. */
final class commerce_795h49_multi_item_fulfillment_test extends \advanced_testcase {
    public function test_each_grant_receives_a_distinct_attempt_execution_reference(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/native/persistence/'
            . 'MoodleCommerceNativeFulfillmentPersistenceRepository.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('attempt_execution_reference($context, $grant)', $source);
        self::assertStringContainsString(
            <<<'PHP'
hash('sha256', $grant->get_reference())
PHP,
            $source
        );
        self::assertStringNotContainsString(
            <<<'PHP'
'executionreference' => $context->get_execution_reference(),
PHP,
            $source
        );
    }

    public function test_paid_purchase_completion_delegates_bundle_expansion_to_grant_planner(): void {
        $completer = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/native/checkout/'
            . 'CommerceNativePaidPurchaseCompleter.php'
        );
        $planner = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/native/checkout/'
            . 'CommerceNativePurchaseGrantPlanner.php'
        );

        self::assertIsString($completer);
        self::assertIsString($planner);

        self::assertStringContainsString(
            'new CommerceNativePurchaseGrantPlanner($this->db)',
            $completer
        );
        self::assertStringContainsString('CommerceBundleExpansionService', $planner);
        self::assertStringContainsString('$expandeditems = $expander->expand(', $planner);
        self::assertStringContainsString('$expandeditem->get_quantity()', $planner);
        self::assertStringContainsString(
            <<<'PHP'
'purchasedsku' => $purchasedsku
PHP,
            $planner
        );
        self::assertStringContainsString(
            <<<'PHP'
'expandedsku' => $sku
PHP,
            $planner
        );
    }

    public function test_running_fulfillment_can_be_retried_and_success_marks_purchase_fulfilled(): void {
        $policy = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/action/CommercePurchaseActionPolicy.php'
        );
        $service = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/action/CommercePurchaseActionService.php'
        );

        self::assertIsString($policy);
        self::assertIsString($service);
        self::assertStringContainsString("['pending', 'running', 'failed', 'error']", $policy);
        self::assertStringContainsString('mark_purchase_fulfilled', $service);
        self::assertStringContainsString("'status' => 'fulfilled'", $service);
    }
}
