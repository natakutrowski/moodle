<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\policy\CommerceReadConsumer;
use local_subscriptions\commerce\read\policy\CommerceReadPolicy;
use local_subscriptions\crm\commerce\shadow\CrmCommerceShadowService;

/**
 * I10C native-first CRM read entry point.
 *
 * The feature flag is authoritative. Legacy remains the safe default and the
 * optional shadow comparison is deliberately non-blocking.
 */
final class I10cCrmCommerceCustomerService {
    public function __construct(
        private readonly ?CommerceReadPolicy $policy = null,
        private readonly ?NativeCrmCommerceCustomerService $native = null,
        private readonly ?LegacyCrmCommerceCustomerService $legacy = null
    ) {
    }

    public function build_snapshot(int $userid, ?string $email = null): CrmCommerceCustomerSnapshot {
        $policy = $this->policy ?? new CommerceReadPolicy();

        if (!$policy->is_native_enabled(CommerceReadConsumer::CRM)) {
            return $this->read_legacy($userid, $email, $policy);
        }

        try {
            $snapshot = ($this->native ?? new NativeCrmCommerceCustomerService())
                ->build_snapshot($userid, $email);

            $this->run_shadow_comparison($userid, $email, $policy);

            return $snapshot;
        } catch (\Throwable $exception) {
            if (!$policy->is_legacy_fallback_enabled()) {
                throw $exception;
            }

            debugging(
                sprintf(
                    '[I10C CRM fallback] user=%d %s: %s',
                    $userid,
                    get_class($exception),
                    $exception->getMessage()
                ),
                DEBUG_DEVELOPER
            );

            return ($this->legacy ?? new LegacyCrmCommerceCustomerService())
                ->build_snapshot($userid, $email);
        }
    }

    private function read_legacy(
        int $userid,
        ?string $email,
        CommerceReadPolicy $policy
    ): CrmCommerceCustomerSnapshot {
        if ($policy->is_shadow_enabled()) {
            return (new CrmCommerceShadowService())
                ->execute($userid, $email)
                ->get_snapshot();
        }

        return ($this->legacy ?? new LegacyCrmCommerceCustomerService())
            ->build_snapshot($userid, $email);
    }

    private function run_shadow_comparison(
        int $userid,
        ?string $email,
        CommerceReadPolicy $policy
    ): void {
        if (!$policy->is_shadow_enabled()) {
            return;
        }

        try {
            (new CrmCommerceShadowService())->execute($userid, $email);
        } catch (\Throwable $exception) {
            debugging(
                sprintf(
                    '[I10C CRM shadow] user=%d %s: %s',
                    $userid,
                    get_class($exception),
                    $exception->getMessage()
                ),
                DEBUG_DEVELOPER
            );
        }
    }
}
