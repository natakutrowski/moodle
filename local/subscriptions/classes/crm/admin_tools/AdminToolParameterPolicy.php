<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines accepted web parameters for each administrative tool.
 */
final class AdminToolParameterPolicy {

    public function default_limit(
        string $toolkey
    ): ?int {
        return match ($toolkey) {
            AdminToolKeys::INTELLIGENCE_SNAPSHOT =>
                500,

            AdminToolKeys::RECOMMENDATIONS =>
                100,

            AdminToolKeys::DIGITAL_RECONCILIATION =>
                10,

            default => null,
        };
    }

    public function maximum_limit(
        string $toolkey
    ): ?int {
        return match ($toolkey) {
            AdminToolKeys::INTELLIGENCE_SNAPSHOT =>
                5000,

            AdminToolKeys::RECOMMENDATIONS =>
                1000,

            AdminToolKeys::DIGITAL_RECONCILIATION =>
                100,

            default => null,
        };
    }

    public function has_limit(
        string $toolkey
    ): bool {
        return
            $this->default_limit($toolkey)
            !== null;
    }

    public function has_reset_cursor(
        string $toolkey
    ): bool {
        return
            $toolkey ===
            AdminToolKeys::RECOMMENDATIONS;
    }

    /**
     * Reads and clamps permitted parameters from the current request.
     *
     * @param string $toolkey
     * @return array
     */
    public function read_request_parameters(
        string $toolkey
    ): array {
        $parameters = [];

        $defaultlimit =
            $this->default_limit($toolkey);

        $maximumlimit =
            $this->maximum_limit($toolkey);

        if (
            $defaultlimit !== null &&
            $maximumlimit !== null
        ) {
            $requestedlimit =
                optional_param(
                    'limit',
                    $defaultlimit,
                    PARAM_INT
                );

            $parameters['limit'] = max(
                1,
                min(
                    $maximumlimit,
                    $requestedlimit
                )
            );
        }

        if (
            $this->has_reset_cursor(
                $toolkey
            )
        ) {
            $parameters['resetcursor'] =
                optional_param(
                    'resetcursor',
                    0,
                    PARAM_BOOL
                );
        }

        return $parameters;
    }
}