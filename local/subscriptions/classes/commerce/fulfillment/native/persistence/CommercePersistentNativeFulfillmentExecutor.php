<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Executes one Native grant with persisted idempotency and attempt history. */
final class CommercePersistentNativeFulfillmentExecutor {
    public function __construct(
        private readonly CommerceNativeFulfillmentHandlerRegistry $registry,
        private readonly CommerceNativeFulfillmentPersistenceRepository $persistence
    ) {
    }

    public function execute(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult {
        $state = $this->persistence->find_state($grant->get_reference());
        if (!$context->is_dry_run() && $state !== null && (string) $state->status === CommerceNativeFulfillmentResult::STATUS_COMPLETED) {
            $this->persistence->activate_grant_if_planned($grant);

            return CommerceNativeFulfillmentResult::skipped(
                $grant,
                'Native fulfillment was already completed for this grant.',
                [
                    'idempotent' => true,
                    'attempts' => (int) $state->attempts,
                    'lastexecutionreference' => (string) $state->lastexecutionreference,
                ]
            );
        }

        try {
            $handler = $this->registry->resolve($grant->get_type());
            $handlerclass = get_class($handler);
        } catch (\Throwable $exception) {
            return CommerceNativeFulfillmentResult::failed(
                $grant,
                $exception->getMessage(),
                get_class($exception),
                ['executionreference' => $context->get_execution_reference()]
            );
        }

        $attemptid = $this->persistence->begin_attempt($grant, $context, $handlerclass);
        try {
            $result = $handler->fulfill($grant, $context);
            if ($result->get_grant()->get_reference() !== $grant->get_reference()) {
                throw new \coding_exception('A Native handler returned a result for another grant.');
            }
        } catch (\Throwable $exception) {
            $result = CommerceNativeFulfillmentResult::failed(
                $grant,
                $exception->getMessage(),
                get_class($exception),
                ['executionreference' => $context->get_execution_reference()]
            );
        }

        $this->persistence->complete_attempt($attemptid, $grant, $context, $handlerclass, $result);

        if (!$context->is_dry_run() && $result->is_completed()) {
            $this->persistence->activate_grant_if_planned($grant);
        }

        return $result;
    }
}
