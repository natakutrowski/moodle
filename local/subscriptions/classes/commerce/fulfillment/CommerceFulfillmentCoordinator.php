<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/**
 * Coordinates fulfillment after successful payment.
 */
final class CommerceFulfillmentCoordinator {

    public function __construct(
        private readonly CommerceFulfillmentHandlerRegistry $registry,
        private readonly ?CommerceFulfillmentOperationValidator $validator = null
    ) {
    }

    /**
     * @return CommerceFulfillmentOperation[]
     */
    public function plan(
        CommercePurchasePreparation $preparation
    ): array {
        $operations = [];

        foreach ($preparation->get_items() as $index => $item) {
            $operations[] = new CommerceFulfillmentOperation(
                sprintf(
                    '%s:item:%d',
                    $preparation->get_reference(),
                    $index + 1
                ),
                $item->get_fulfillment_key(),
                $item->get_fulfillment_metadata()
            );
        }

        return $operations;
    }

    /**
     * @param CommerceFulfillmentOperation[] $operations
     */
    public function fulfill(
        array $operations,
        CommerceFulfillmentContext $context
    ): CommerceFulfillmentBatchResult {
        $validator = $this->validator
            ?? new CommerceFulfillmentOperationValidator();

        $validator->validate_context($context);

        $results = [];

        foreach ($operations as $operation) {
            if (!$operation instanceof CommerceFulfillmentOperation) {
                throw new \coding_exception(
                    'Invalid Commerce fulfillment operation.'
                );
            }

            try {
                $validator->validate_operation(
                    $operation,
                    $context
                );

                $handler = $this->registry->resolve(
                    $operation
                );

                $results[] = $handler->fulfill(
                    $operation,
                    $context
                );
            } catch (\Throwable $exception) {
                $results[] = new CommerceFulfillmentResult(
                    $operation,
                    CommerceFulfillmentResult::STATUS_FAILED,
                    $exception->getMessage(),
                    [
                        'exception' => get_class($exception),
                        'idempotency_key' =>
                            $operation->get_idempotency_key(),
                    ]
                );
            }
        }

        return new CommerceFulfillmentBatchResult(
            $context,
            $results
        );
    }
}
