<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\reconciliation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\command\dto\CommerceCommandRequest;
use local_subscriptions\commerce\command\service\CommercePurchaseCommandService;
use local_subscriptions\commerce\reconciliation\dto\CommerceReconciliationIssue;
use local_subscriptions\commerce\reconciliation\dto\CommerceReconciliationResult;

final class CommerceReconciliationService {
    public function __construct(
        private readonly CommercePurchaseCommandService $commands,
        private readonly CommerceReconciliationPolicy $policy
    ) {
    }

    public function reconcile(string $family, int $legacyid, bool $repair = false): CommerceReconciliationResult {
        if (!$this->policy->is_enabled()) {
            return new CommerceReconciliationResult($family, $legacyid, []);
        }

        $result = $this->commands->synchronise(
            new CommerceCommandRequest($family, $legacyid, 'reconciliation', 'task', 'reconcile:' . $family . ':' . $legacyid)
        );

        if ($result->is_successful()) {
            return new CommerceReconciliationResult($family, $legacyid, [], $repair && $this->policy->repair_enabled());
        }

        return new CommerceReconciliationResult($family, $legacyid, [
            new CommerceReconciliationIssue(
                $family,
                $legacyid,
                'native_sync_failed',
                'critical',
                $result->get_error_message() ?? 'Native Commerce synchronisation failed.'
            ),
        ]);
    }
}
