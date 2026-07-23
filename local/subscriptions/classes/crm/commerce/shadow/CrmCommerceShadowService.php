<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\commerce\CrmCommerceCustomerService;
use local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService;

/**
 * Executes the Commerce domain and legacy CRM implementations in parallel.
 *
 * No database write is performed.
 */
class CrmCommerceShadowService {

    public function __construct(
        private readonly ?CrmCommerceCustomerService $commerceservice = null,
        private readonly ?LegacyCrmCommerceCustomerService $legacyservice = null,
        private readonly ?CrmCommerceSnapshotComparator $comparator = null
    ) {
    }

    public function execute(
        int $userid,
        ?string $email = null
    ): CrmCommerceShadowResult {
        $commerceservice = $this->commerceservice
            ?? new CrmCommerceCustomerService();

        $legacyservice = $this->legacyservice
            ?? new LegacyCrmCommerceCustomerService();

        $comparator = $this->comparator
            ?? new CrmCommerceSnapshotComparator();

        $commercesnapshot = null;
        $legacysnapshot = null;
        $commerceerror = null;
        $legacyerror = null;

        try {
            $commercesnapshot =
                $commerceservice->build_snapshot(
                    $userid,
                    $email
                );
        } catch (\Throwable $exception) {
            $commerceerror =
                get_class($exception)
                . ': '
                . $exception->getMessage();
        }

        try {
            $legacysnapshot =
                $legacyservice->build_snapshot(
                    $userid,
                    $email
                );
        } catch (\Throwable $exception) {
            $legacyerror =
                get_class($exception)
                . ': '
                . $exception->getMessage();
        }

        if ($commercesnapshot === null) {
            if ($legacysnapshot === null) {
                throw new \runtime_exception(
                    'Both Commerce and legacy CRM snapshots failed.'
                );
            }

            debugging(
                sprintf(
                    '[Commerce shadow] Commerce failed for user %d: %s',
                    $userid,
                    (string)$commerceerror
                ),
                DEBUG_DEVELOPER
            );

            return new CrmCommerceShadowResult(
                $legacysnapshot,
                null,
                true,
                $commerceerror,
                $legacyerror
            );
        }

        if ($legacysnapshot === null) {
            debugging(
                sprintf(
                    '[Commerce shadow] Legacy comparison failed for user %d: %s',
                    $userid,
                    (string)$legacyerror
                ),
                DEBUG_DEVELOPER
            );

            return new CrmCommerceShadowResult(
                $commercesnapshot,
                null,
                false,
                $commerceerror,
                $legacyerror
            );
        }

        $comparison = $comparator->compare(
            $commercesnapshot,
            $legacysnapshot
        );

        if (!$comparison->is_equivalent()) {
            debugging(
                sprintf(
                    '[Commerce shadow] %d difference(s) detected for user %d.',
                    $comparison->get_difference_count(),
                    $userid
                ),
                DEBUG_DEVELOPER
            );
        }

        return new CrmCommerceShadowResult(
            $commercesnapshot,
            $comparison,
            false,
            null,
            null
        );
    }
}