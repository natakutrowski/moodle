<?php

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\commerce\shadow\CrmCommerceShadowService;

/**
 * Safe CRM entry point for Commerce customer information.
 *
 * During Phase 7.93A, the service operates in shadow mode:
 * the Commerce domain result is returned while legacy aggregates are
 * calculated silently for compatibility comparison.
 */
final class SafeCrmCommerceCustomerService {

    public function __construct(
        private readonly ?CrmCommerceShadowService $shadowservice = null
    ) {
    }

    public function build_snapshot(
        int $userid,
        ?string $email = null
    ): CrmCommerceCustomerSnapshot {
        $shadowservice = $this->shadowservice
            ?? new CrmCommerceShadowService();

        return $shadowservice
            ->execute(
                $userid,
                $email
            )
            ->get_snapshot();
    }
}