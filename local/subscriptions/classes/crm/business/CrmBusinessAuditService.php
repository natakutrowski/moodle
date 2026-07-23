<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\audit\CommerceCompatibilityAuditor;
use local_subscriptions\commerce\audit\CommerceCompatibilityReport;
use local_subscriptions\commerce\audit\CommerceLegacyComparator;
use local_subscriptions\commerce\audit\CommerceLegacyComparisonResult;
use local_subscriptions\crm\commerce\SafeCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot;

/**
 * Entry point for the CRM business-data audit.
 */
final class CrmBusinessAuditService {

    public function __construct(
        private readonly ?CrmBusinessAuditRepository $repository = null
    ) {
    }

    public function run(): CrmBusinessAuditReport {
        $repository = $this->repository
            ?? new CrmBusinessAuditRepository();

        return $repository->build_report();
    }

    /**
     * Runs the read-only Commerce compatibility audit.
     */
    public function run_commerce_compatibility_audit(
        int $batchsize = 200
    ): CommerceCompatibilityReport {
        $auditor = new CommerceCompatibilityAuditor();

        return $auditor->audit(
            $batchsize
        );
    }

    /**
     * Compares historical Commerce values with their new domain objects.
     */
    public function compare_legacy_commerce(
        int $batchsize = 200
    ): CommerceLegacyComparisonResult {
        $comparator = new CommerceLegacyComparator();

        return $comparator->compare(
            $batchsize
        );
    }

    /**
     * Builds the unified read-only Commerce snapshot used by future
     * CRM User 360° integrations.
     */
    public function get_customer_commerce_snapshot(
        int $userid,
        ?string $email = null
    ): CrmCommerceCustomerSnapshot {
        $service = new SafeCrmCommerceCustomerService();

        return $service->build_snapshot(
            $userid,
            $email
        );
    }
}