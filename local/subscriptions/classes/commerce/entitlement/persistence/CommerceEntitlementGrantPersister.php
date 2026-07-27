<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;

/**
 * Persists a planned entitlement ledger atomically and idempotently.
 */
final class CommerceEntitlementGrantPersister {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceEntitlementGrantRepository $repository
    ) {
    }

    public function persist(
        CommerceEntitlementGrantPlan $plan,
        ?int $now = null
    ): CommerceEntitlementGrantPersistenceResult {
        $transaction = $this->db->start_delegated_transaction();
        $created = 0;
        $identical = 0;
        $conflicts = 0;
        $records = [];

        try {
            foreach ($plan->get_grants() as $grant) {
                $classification = $this->repository->classify($grant);

                if ($classification === 'create') {
                    $records[] = $this->repository->insert($grant, $now);
                    $created++;
                    continue;
                }

                if ($classification === 'identical') {
                    $record = $this->repository->find_by_idempotency_key(
                        $grant->get_idempotency_key()
                    );

                    if ($record !== null) {
                        $records[] = $record;
                    }

                    $identical++;
                    continue;
                }

                $conflicts++;
            }

            if ($conflicts > 0) {
                throw new \RuntimeException(
                    'Native Commerce entitlement persistence detected an idempotency conflict.'
                );
            }

            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }

        return new CommerceEntitlementGrantPersistenceResult(
            $created,
            $identical,
            $conflicts,
            $records
        );
    }
}
