<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Structural certification for H4.4E Native purchase completion. */
final class commerce_795h44e_native_purchase_completion_test extends \advanced_testcase {
    public function test_event_router_dispatches_every_synchronized_native_checkout(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/payment/EventRouter.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('CommerceNativePaidPurchaseCompleter', $source);
        $this->assertStringContainsString(
            "\$event->type === 'checkout_completed' && \$synchronized",
            $source
        );
        $this->assertStringContainsString('->complete($event)', $source);
    }

    public function test_payment_synchronizer_enriches_fallback_resolved_event_identity(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/payment/returnflow/'
            . 'CommercePaymentEventSynchronizer.php'
        );
        $this->assertIsString($source);
        $this->assertStringContainsString('enrich_event_identity($event, $attempt)', $source);
        $this->assertStringContainsString("\$event->meta['commerce_payment_id']", $source);
        $this->assertStringContainsString("\$event->meta['commerce_purchase_uuid']", $source);
    }

    public function test_completer_delegates_deterministic_grant_planning(): void {
        $completer = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/native/checkout/'
            . 'CommerceNativePaidPurchaseCompleter.php'
        );
        $planner = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/native/checkout/'
            . 'CommerceNativePurchaseGrantPlanner.php'
        );

        $this->assertIsString($completer);
        $this->assertIsString($planner);
        $this->assertStringContainsString('CommerceNativePurchaseGrantPlanner', $completer);
        $this->assertStringContainsString('CommerceEntitlementGrantPersister', $completer);
        $this->assertStringContainsString('CommerceNativePurchaseFulfillmentOrchestrator', $completer);
        $this->assertStringContainsString('CommercePurchaseStatus::FULFILLED', $completer);
        $this->assertStringContainsString('CommerceProductPriceRepository', $planner);
        $this->assertStringContainsString('CommerceBundleExpansionService', $planner);
        $this->assertStringContainsString('CommerceEffectiveEntitlementResolver', $planner);
        $this->assertStringContainsString("'expandedsku' => \$sku", $planner);
        $this->assertStringContainsString('resolve_product_sku', $planner);
        $this->assertStringContainsString('extract_price_id', $planner);
        $this->assertStringNotContainsString('payment_request_id', $completer);
    }
}
