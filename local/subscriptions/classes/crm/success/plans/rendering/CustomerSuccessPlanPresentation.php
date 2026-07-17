<?php

namespace local_subscriptions\crm\success\plans\rendering;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;

/**
 * Central presentation layer for Customer Success plans.
 *
 * Technical values remain stable in storage.
 * Human-readable labels are resolved in the active Moodle language.
 */
final class CustomerSuccessPlanPresentation {

    private const OBJECTIVE_PREFIX =
        '[[csplan-objective:';

    private const DESCRIPTION_PREFIX =
        '[[csplan-description:recommendations:';

    /**
     * @return string[]
     */
    public static function objective_keys(): array {
        return [
            'reduce_churn_risk',
            'resolve_payment_friction',
            'resolve_support_pressure',
            'restore_learning_access',
            'restore_learning_engagement',
            'develop_customer_opportunity',
            'coordinate_customer_success',
        ];
    }

    public static function generated_title_value(
        string $objectivekey
    ): string {
        $objectivekey =
            self::normalize_objective_key(
                $objectivekey
            );

        return
            self::OBJECTIVE_PREFIX .
            $objectivekey .
            ']]';
    }

    public static function generated_description_value(
        int $recommendationcount
    ): string {
        return
            self::DESCRIPTION_PREFIX .
            max(0, $recommendationcount) .
            ']]';
    }

    public static function title(
        string $objectivekey,
        string $storedtitle
    ): string {
        $storedtitle = trim(
            $storedtitle
        );

        $markerobjective =
            self::objective_from_marker(
                $storedtitle
            );

        if ($markerobjective !== null) {
            return self::objective_label(
                $markerobjective
            );
        }

        return format_string(
            $storedtitle
        );
    }

    public static function description(
        ?string $storeddescription
    ): ?string {
        if ($storeddescription === null) {
            return null;
        }

        $storeddescription = trim(
            $storeddescription
        );

        if ($storeddescription === '') {
            return null;
        }

        $recommendationcount =
            self::recommendation_count_from_marker(
                $storeddescription
            );

        if ($recommendationcount !== null) {
            return get_string(
                'csplandescription_recommendations',
                'local_subscriptions',
                $recommendationcount
            );
        }

        return $storeddescription;
    }

    public static function objective_label(
        string $objectivekey
    ): string {
        $objectivekey =
            self::normalize_objective_key(
                $objectivekey
            );

        return get_string(
            'csplanobjective_' .
                $objectivekey,
            'local_subscriptions'
        );
    }

    public static function status_label(
        string $status
    ): string {
        if (
            !CustomerSuccessPlanStatus::is_valid(
                $status
            )
        ) {
            return $status;
        }

        return get_string(
            'csplanstatus_' . $status,
            'local_subscriptions'
        );
    }

    public static function step_status_label(
        string $status
    ): string {
        if (
            !CustomerSuccessPlanStepStatus::is_valid(
                $status
            )
        ) {
            return $status;
        }

        return get_string(
            'csplanstepstatus_' . $status,
            'local_subscriptions'
        );
    }

    public static function priority_label(
        string $priority
    ): string {
        if (
            !in_array(
                $priority,
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'critical',
                ],
                true
            )
        ) {
            return $priority;
        }

        return get_string(
            'csplanpriority_' . $priority,
            'local_subscriptions'
        );
    }

    public static function source_label(
        string $source
    ): string {
        if (
            !CustomerSuccessPlanSource::is_valid(
                $source
            )
        ) {
            return $source;
        }

        return get_string(
            'csplansource_' . $source,
            'local_subscriptions'
        );
    }

    public static function blocked_reason_label(
        ?string $reason
    ): ?string {
        if ($reason === null) {
            return null;
        }

        $reason = trim(
            $reason
        );

        if ($reason === '') {
            return null;
        }

        $key = match ($reason) {
            'dependency_cycle' =>
                'csplanblockedreason_dependency_cycle',

            'manual' =>
                'csplanblockedreason_manual',

            default =>
                'csplanblockedreason_unknown',
        };

        return get_string(
            $key,
            'local_subscriptions',
            $reason
        );
    }

    private static function normalize_objective_key(
        string $objectivekey
    ): string {
        return in_array(
            $objectivekey,
            self::objective_keys(),
            true
        )
            ? $objectivekey
            : 'coordinate_customer_success';
    }

    private static function objective_from_marker(
        string $value
    ): ?string {
        if (
            !str_starts_with(
                $value,
                self::OBJECTIVE_PREFIX
            ) ||
            !str_ends_with(
                $value,
                ']]'
            )
        ) {
            return null;
        }

        $objectivekey = substr(
            $value,
            strlen(
                self::OBJECTIVE_PREFIX
            ),
            -2
        );

        return in_array(
            $objectivekey,
            self::objective_keys(),
            true
        )
            ? $objectivekey
            : null;
    }

    private static function recommendation_count_from_marker(
        string $value
    ): ?int {
        if (
            !preg_match(
                '/^\\[\\[csplan-description:recommendations:(\\d+)\\]\\]$/',
                $value,
                $matches
            )
        ) {
            return null;
        }

        return (int)$matches[1];
    }
}