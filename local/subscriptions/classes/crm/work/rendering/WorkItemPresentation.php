<?php

namespace local_subscriptions\crm\work\rendering;

defined('MOODLE_INTERNAL') || die();

final class WorkItemPresentation {

    public static function status_label(
        string $status
    ): string {
        return self::domain_label(
            'crm_work_status_',
            $status
        );
    }

    public static function priority_label(
        string $priority
    ): string {
        return self::domain_label(
            'crm_work_priority_',
            $priority
        );
    }

    public static function type_label(
        string $type
    ): string {
        return self::domain_label(
            'crm_work_type_',
            $type
        );
    }

    public static function source_label(
        string $source
    ): string {
        $stringkey =
            'crm_work_source_' .
            clean_param(
                $source,
                PARAM_ALPHANUMEXT
            );

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return self::fallback_label(
            $source
        );
    }

    public static function status_class(string $status): string {
        return match ($status) {
            'resolved', 'closed' => 'bg-success',
            'blocked' => 'bg-danger',
            'waiting' => 'bg-warning text-dark',
            'cancelled' => 'bg-secondary',
            'in_progress' => 'bg-primary',
            default => 'bg-info text-dark',
        };
    }

    public static function priority_class(string $priority): string {
        return match ($priority) {
            'critical' => 'bg-dark',
            'urgent' => 'bg-danger',
            'high' => 'bg-warning text-dark',
            'low' => 'bg-light text-dark border',
            default => 'bg-secondary',
        };
    }

    private static function fallback_label(
        string $value
    ): string {
        $value = str_replace(
            [
                '_',
                '.',
                '-',
            ],
            ' ',
            trim($value)
        );

        return ucfirst($value);
    }

    private static function domain_label(
        string $prefix,
        string $value
    ): string {
        $normalizedvalue = clean_param(
            $value,
            PARAM_ALPHANUMEXT
        );

        $stringkey =
            $prefix .
            $normalizedvalue;

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return self::fallback_label(
            $value
        );
    }

}