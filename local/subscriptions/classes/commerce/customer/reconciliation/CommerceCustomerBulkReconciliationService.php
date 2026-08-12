<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\reconciliation;

defined('MOODLE_INTERNAL') || die();

/** Safe bulk orchestration. Every execute re-evaluates identity at write time. */
final class CommerceCustomerBulkReconciliationService {
    public const MAX_BATCH = 500;

    public function __construct(
        private readonly CommerceCustomerIdentityReconciliationService $reconciliation
    ) {}

    /** @return CommerceCustomerIdentityReconciliationPreview[] */
    public function preview(array $purchaseids): array {
        $previews = [];
        foreach ($this->normalise_ids($purchaseids) as $purchaseid) {
            $previews[] = $this->reconciliation->preview_purchase($purchaseid);
        }
        return $previews;
    }

    /** @return CommerceCustomerIdentityReconciliationResult[] */
    public function execute(array $purchaseids): array {
        $results = [];
        foreach ($this->normalise_ids($purchaseids) as $purchaseid) {
            $results[] = $this->reconciliation->reconcile_purchase($purchaseid, true);
        }
        return $results;
    }

    /** @return int[] */
    private function normalise_ids(array $purchaseids): array {
        $ids = [];
        foreach ($purchaseids as $purchaseid) {
            $purchaseid = (int)$purchaseid;
            if ($purchaseid > 0) {
                $ids[$purchaseid] = $purchaseid;
            }
            if (count($ids) >= self::MAX_BATCH) {
                break;
            }
        }
        return array_values($ids);
    }
}
