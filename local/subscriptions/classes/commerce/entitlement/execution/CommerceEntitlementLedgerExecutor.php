<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\execution;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationContext;
use local_subscriptions\commerce\entitlement\application\CommerceEntitlementApplicationExecutor;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;

/**
 * Persists and applies entitlement grants with ledger-backed idempotence.
 */
final class CommerceEntitlementLedgerExecutor {
    public function __construct(
        private readonly CommerceEntitlementGrantPersister $persister,
        private readonly CommerceEntitlementGrantRepository $repository,
        private readonly CommerceEntitlementApplicationExecutor $applicationexecutor
    ) {
    }

    public function execute(
        CommerceEntitlementGrantPlan $plan,
        CommerceEntitlementApplicationContext $context
    ): CommerceEntitlementLedgerExecutionResult {
        $persistence = $this->persister->persist($plan);
        $applied = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($plan->get_grants() as $grant) {
            $record = $this->repository->find_by_idempotency_key($grant->get_idempotency_key());

            if ($record !== null && (string)$record->status === 'applied') {
                $skipped++;
                continue;
            }

            $this->repository->update_status_by_idempotency_key(
                $grant->get_idempotency_key(),
                'applying',
                $context->get_applied_at()
            );

            $result = $this->applicationexecutor->execute($grant, $context);
            $status = $result->is_applied() ? 'applied' : 'failed';

            $this->repository->update_status_by_idempotency_key(
                $grant->get_idempotency_key(),
                $status,
                $context->get_applied_at()
            );

            if ($result->is_applied()) {
                $applied++;
            } else {
                $failed++;
            }
        }

        return new CommerceEntitlementLedgerExecutionResult(
            $persistence->get_created(),
            $persistence->get_identical(),
            $applied,
            $skipped,
            $failed
        );
    }
}
