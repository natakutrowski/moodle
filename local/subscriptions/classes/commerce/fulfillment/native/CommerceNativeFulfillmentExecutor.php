<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Dispatches one entitlement grant to its Native handler.
 */
final class CommerceNativeFulfillmentExecutor {
    public function __construct(
        private readonly CommerceNativeFulfillmentHandlerRegistry $registry
    ) {
    }

    public function supports(CommerceEntitlementGrant $grant): bool {
        return $this->registry->supports($grant->get_type());
    }

    public function execute(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult {
        try {
            $handler = $this->registry->resolve($grant->get_type());
            $result = $handler->fulfill($grant, $context);

            if ($result->get_grant()->get_reference() !== $grant->get_reference()) {
                throw new \coding_exception(
                    'A Native Commerce fulfillment handler returned a result for another grant.'
                );
            }

            return $result;
        } catch (\Throwable $exception) {
            return CommerceNativeFulfillmentResult::failed(
                $grant,
                $exception->getMessage(),
                get_class($exception),
                [
                    'executionreference' => $context->get_execution_reference(),
                    'source' => $context->get_source(),
                    'dryrun' => $context->is_dry_run(),
                ]
            );
        }
    }
}
