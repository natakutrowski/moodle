<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\batch\CommerceNativePurchaseFulfillmentOrchestrator;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;
use local_subscriptions\commerce\shadow\runtime\CommerceShadowPurchaseReferenceResolver;
use local_subscriptions\payment\dto\InternalEvent;

class CommerceNativeRuntimeExecutor {
    public function execute(InternalEvent $event, string $entrypoint): void {
        global $DB;
        $legacyrequestid = (int) ($event->payment_request_id ?? $event->meta['payment_request_id'] ?? 0);
        $legacyfamily = $this->legacy_family($event);
        $reference = (new CommerceShadowPurchaseReferenceResolver())->resolve($legacyrequestid, $legacyfamily);
        if ($reference === null) {
            throw new \RuntimeException('Native Commerce purchase reference was not resolved.');
        }

        $registry = new CommerceNativeFulfillmentHandlerRegistry([
            new CommerceCourseAccessFulfillmentHandler(),
            new CommerceDigitalDownloadFulfillmentHandler(),
        ]);
        $orchestrator = new CommerceNativePurchaseFulfillmentOrchestrator(
            new CommerceEntitlementGrantRepository(
                $DB,
                new CommerceEntitlementGrantRecordMapper()
            ),
            new CommerceEntitlementGrantRecordMapper(),
            new CommercePersistentNativeFulfillmentExecutor(
                $registry,
                new MoodleCommerceNativeFulfillmentPersistenceRepository()
            )
        );
        $result = $orchestrator->execute_purchase(
            $reference,
            CommerceNativeFulfillmentContext::runtime(
                'runtime-' . substr(hash('sha256', $entrypoint . '|' . $reference . '|' . microtime(true)), 0, 24),
                time(),
                null,
                'runtime_switch',
                ['entrypoint' => $entrypoint]
            )
        );
        if ($result->count() === 0 || !$result->is_successful()) {
            throw new \RuntimeException('Native Commerce fulfillment did not complete successfully.');
        }
    }
    private function legacy_family(InternalEvent $event): string {
        $paymentcontext = strtolower(trim((string)($event->meta['payment_context'] ?? '')));
        $paymentrequesttable = strtolower(trim((string)($event->meta['payment_request_table'] ?? '')));

        if ($paymentcontext === 'digital_product' || str_contains($paymentrequesttable, 'digital')) {
            return 'digital';
        }

        return 'subscription';
    }

}
