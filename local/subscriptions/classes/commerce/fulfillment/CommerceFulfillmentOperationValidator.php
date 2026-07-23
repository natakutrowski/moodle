<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates fulfillment operations against a confirmed payment context.
 */
final class CommerceFulfillmentOperationValidator {

    public function validate_context(
        CommerceFulfillmentContext $context
    ): void {
        if (!$context->is_payment_confirmed()) {
            throw new CommerceFulfillmentExecutionException(
                'Commerce fulfillment requires a confirmed payment.'
            );
        }

        if ($context->get_provider() === '') {
            throw new CommerceFulfillmentExecutionException(
                'Commerce fulfillment requires a payment provider.'
            );
        }

        if ($context->get_transaction_id() === '') {
            throw new CommerceFulfillmentExecutionException(
                'Commerce fulfillment requires a transaction identifier.'
            );
        }

        if ($context->get_currency() === '') {
            throw new CommerceFulfillmentExecutionException(
                'Commerce fulfillment requires a payment currency.'
            );
        }
    }

    public function validate_operation(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): void {
        $this->validate_context($context);

        if (
            !str_starts_with(
                $operation->get_reference(),
                $context->get_reference() . ':item:'
            )
        ) {
            throw new CommerceFulfillmentExecutionException(
                'The fulfillment operation does not belong to the supplied payment context.'
            );
        }
    }
}
