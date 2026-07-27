<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\batch;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;

/** Executes all persisted Native entitlement grants for one purchase. */
final class CommerceNativePurchaseFulfillmentOrchestrator {
    public function __construct(
        private readonly CommerceEntitlementGrantRepository $grants,
        private readonly CommerceEntitlementGrantRecordMapper $mapper,
        private readonly CommercePersistentNativeFulfillmentExecutor $executor
    ) {
    }

    public function execute_purchase(
        string $purchasereference,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentBatchResult {
        $purchasereference = trim($purchasereference);
        if ($purchasereference === '') {
            throw new \coding_exception('A purchase reference is required for Native fulfillment.');
        }

        $results = [];
        foreach ($this->grants->find_by_purchase_reference($purchasereference) as $record) {
            try {
                $grant = $this->mapper->from_record($record);
                $results[] = $this->executor->execute($grant, $context);
            } catch (\Throwable $exception) {
                $grant = $this->fallback_grant($record, $purchasereference);
                $results[] = CommerceNativeFulfillmentResult::failed(
                    $grant,
                    $exception->getMessage(),
                    get_class($exception),
                    ['executionreference' => $context->get_execution_reference()]
                );
            }
        }

        return new CommerceNativeFulfillmentBatchResult($purchasereference, $context, $results);
    }

    private function fallback_grant(\stdClass $record, string $purchasereference): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            (string)($record->grantreference ?? 'invalid-' . sha1(serialize($record))),
            $purchasereference,
            (string)($record->itemreference ?? 'invalid-item'),
            (string)($record->productsku ?? 'INVALID'),
            preg_match('/^[a-z][a-z0-9_]{1,63}$/', (string)($record->type ?? '')) ? (string)$record->type : 'invalid_grant',
            (string)($record->resourcekey ?? 'invalid-resource'),
            max(1, (int)($record->quantity ?? 1)),
            !empty($record->beneficiaryuserid) ? (int)$record->beneficiaryuserid : null,
            validate_email((string)($record->beneficiaryemail ?? '')) ? (string)$record->beneficiaryemail : 'invalid@example.invalid',
            max(1, (int)($record->validfrom ?? time())),
            null
        );
    }
}
