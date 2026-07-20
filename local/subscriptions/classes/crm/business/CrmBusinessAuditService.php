<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

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
}