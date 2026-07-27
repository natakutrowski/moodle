<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\application;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/**
 * Executes one entitlement grant through its registered handler.
 */
final class CommerceEntitlementApplicationExecutor {
    public function __construct(
        private readonly CommerceEntitlementApplicationHandlerRegistry $registry
    ) {
    }

    public function supports(CommerceEntitlementGrant $grant): bool {
        return $this->registry->supports($grant->get_type());
    }

    public function execute(
        CommerceEntitlementGrant $grant,
        CommerceEntitlementApplicationContext $context
    ): CommerceEntitlementApplicationResult {
        try {
            $payload = $this->registry
                ->resolve($grant->get_type())
                ->apply($grant, $context);

            return new CommerceEntitlementApplicationResult(
                $grant,
                CommerceEntitlementApplicationResult::STATUS_APPLIED,
                $payload
            );
        } catch (\Throwable $exception) {
            return new CommerceEntitlementApplicationResult(
                $grant,
                CommerceEntitlementApplicationResult::STATUS_FAILED,
                ['exception' => get_class($exception)],
                $exception->getMessage()
            );
        }
    }
}
