<?php

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\commerce\shadow\CrmCommerceShadowService;

/**
 * Safe CRM entry point for Commerce customer information.
 *
 * The service supports both migration modes:
 * - shadow mode through CrmCommerceShadowService;
 * - direct Commerce mode with a Legacy fallback.
 */
final class SafeCrmCommerceCustomerService {

    private readonly ?CrmCommerceShadowService $shadowservice;
    private readonly ?CrmCommerceCustomerService $commerceservice;
    private readonly ?LegacyCrmCommerceCustomerService $legacyservice;

    public function __construct(
        CrmCommerceShadowService|CrmCommerceCustomerService|null $service = null,
        ?LegacyCrmCommerceCustomerService $legacyservice = null
    ) {
        if ($service instanceof CrmCommerceShadowService) {
            if ($legacyservice !== null) {
                throw new \coding_exception(
                    'A Legacy CRM Commerce service cannot be supplied together with a shadow service.'
                );
            }

            $this->shadowservice = $service;
            $this->commerceservice = null;
            $this->legacyservice = null;
            return;
        }

        $this->shadowservice = null;
        $this->commerceservice = $service;
        $this->legacyservice = $legacyservice;
    }

    public function build_snapshot(
        int $userid,
        ?string $email = null
    ): CrmCommerceCustomerSnapshot {
        if ($this->shadowservice !== null) {
            return $this->shadowservice
                ->execute($userid, $email)
                ->get_snapshot();
        }

        // Preserve the current default behaviour: no explicit dependencies means shadow mode.
        if ($this->commerceservice === null && $this->legacyservice === null) {
            return (new RuntimeCrmCommerceCustomerService())
                ->build_snapshot($userid, $email);
        }

        $commerceservice = $this->commerceservice
            ?? new CrmCommerceCustomerService();

        try {
            return $commerceservice->build_snapshot($userid, $email);
        } catch (\Throwable $exception) {
            $legacyservice = $this->legacyservice
                ?? new LegacyCrmCommerceCustomerService();

            debugging(
                sprintf(
                    '[Commerce safe fallback] Commerce failed for user %d: %s: %s',
                    $userid,
                    get_class($exception),
                    $exception->getMessage()
                ),
                DEBUG_DEVELOPER
            );

            return $legacyservice->build_snapshot($userid, $email);
        }
    }
}